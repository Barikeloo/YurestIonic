<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\UploadProductPhoto\UploadProductPhoto;
use App\Product\Application\UploadProductPhoto\UploadProductPhotoCommand;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Event\ProductPhotoUpdated;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenAlreadyUsedException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenExpiredException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenNotFoundException;
use App\Product\Domain\Interfaces\ProductPhotoStorageInterface;
use App\Product\Domain\Interfaces\ProductPhotoUploadNotifierInterface;
use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class UploadProductPhotoTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProductPhotoUploadTokenRepositoryInterface&MockInterface $tokenRepo;
    private ProductRepositoryInterface&MockInterface $productRepo;
    private ProductPhotoStorageInterface&MockInterface $storage;
    private ProductPhotoUploadNotifierInterface&MockInterface $notifier;
    private EventBusInterface&MockInterface $eventBus;
    private UploadProductPhoto $useCase;

    protected function setUp(): void
    {
        $this->tokenRepo   = Mockery::mock(ProductPhotoUploadTokenRepositoryInterface::class);
        $this->productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $this->storage     = Mockery::mock(ProductPhotoStorageInterface::class);
        $this->notifier    = Mockery::mock(ProductPhotoUploadNotifierInterface::class);
        $this->eventBus    = Mockery::mock(EventBusInterface::class);
        $this->useCase     = new UploadProductPhoto(
            $this->tokenRepo,
            $this->productRepo,
            $this->storage,
            $this->notifier,
            $this->eventBus,
        );
    }

    public function test_valid_upload_stores_photo_and_publishes_event(): void
    {
        $restaurantId = Uuid::generate();
        $productId    = Uuid::generate();
        $token        = $this->makeUsableToken($productId, $restaurantId);
        $product      = $this->makeProduct();

        $this->tokenRepo->shouldReceive('findByToken')->with('valid')->andReturn($token);
        $this->productRepo->shouldReceive('findByIdAndRestaurant')
            ->with($productId->value(), $restaurantId->value())
            ->andReturn($product);
        $this->storage->shouldReceive('store')
            ->with('/tmp/photo.jpg', $restaurantId->value(), $productId->value(), '/img.png')
            ->andReturn('http://cdn.test/products/new.webp');
        $this->productRepo->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (Product $p) => $p->imageSrc()->value() === 'http://cdn.test/products/new.webp'));
        $this->tokenRepo->shouldReceive('markAsUsed')->once()->with($token);
        $this->eventBus->shouldReceive('publish')->once()->with(Mockery::type(ProductPhotoUpdated::class));
        $this->notifier->shouldReceive('uploaded')->once()->with($token->token(), $productId->value(), 'http://cdn.test/products/new.webp');

        $response = ($this->useCase)(new UploadProductPhotoCommand(
            token: 'valid',
            temporaryPath: '/tmp/photo.jpg',
        ));

        $this->assertSame('http://cdn.test/products/new.webp', $response->imageSrc);
        $this->assertSame('Test Product', $response->productName);
    }

    public function test_unknown_token_throws_not_found(): void
    {
        $this->tokenRepo->shouldReceive('findByToken')->with('unknown')->andReturn(null);

        $this->expectException(ProductPhotoUploadTokenNotFoundException::class);

        ($this->useCase)(new UploadProductPhotoCommand(token: 'unknown', temporaryPath: '/tmp/photo.jpg'));
    }

    public function test_expired_token_throws_expired(): void
    {
        $token = $this->makeExpiredToken(Uuid::generate(), Uuid::generate());
        $this->tokenRepo->shouldReceive('findByToken')->with('expired')->andReturn($token);

        $this->expectException(ProductPhotoUploadTokenExpiredException::class);

        ($this->useCase)(new UploadProductPhotoCommand(token: 'expired', temporaryPath: '/tmp/p.jpg'));
    }

    public function test_used_token_throws_already_used(): void
    {
        $token = $this->makeUsedToken(Uuid::generate(), Uuid::generate());
        $this->tokenRepo->shouldReceive('findByToken')->with('used')->andReturn($token);

        $this->expectException(ProductPhotoUploadTokenAlreadyUsedException::class);

        ($this->useCase)(new UploadProductPhotoCommand(token: 'used', temporaryPath: '/tmp/p.jpg'));
    }

    public function test_product_not_found_throws_product_not_found(): void
    {
        $restaurantId = Uuid::generate();
        $productId    = Uuid::generate();
        $token        = $this->makeUsableToken($productId, $restaurantId);

        $this->tokenRepo->shouldReceive('findByToken')->with('valid')->andReturn($token);
        $this->productRepo->shouldReceive('findByIdAndRestaurant')
            ->with($productId->value(), $restaurantId->value())
            ->andReturn(null);

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)(new UploadProductPhotoCommand(token: 'valid', temporaryPath: '/tmp/p.jpg'));
    }

    private function makeUsableToken(Uuid $productId, Uuid $restaurantId): \App\Product\Domain\Entity\ProductPhotoUploadToken
    {
        return \App\Product\Domain\Entity\ProductPhotoUploadToken::fromPersistence(
            id: Uuid::generate()->value(),
            token: str_repeat('a', 64),
            productId: $productId->value(),
            restaurantId: $restaurantId->value(),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            usedAt: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }

    private function makeExpiredToken(Uuid $productId, Uuid $restaurantId): \App\Product\Domain\Entity\ProductPhotoUploadToken
    {
        return \App\Product\Domain\Entity\ProductPhotoUploadToken::fromPersistence(
            id: Uuid::generate()->value(),
            token: str_repeat('b', 64),
            productId: $productId->value(),
            restaurantId: $restaurantId->value(),
            expiresAt: new \DateTimeImmutable('-1 hour'),
            usedAt: null,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }

    private function makeUsedToken(Uuid $productId, Uuid $restaurantId): \App\Product\Domain\Entity\ProductPhotoUploadToken
    {
        return \App\Product\Domain\Entity\ProductPhotoUploadToken::fromPersistence(
            id: Uuid::generate()->value(),
            token: str_repeat('c', 64),
            productId: $productId->value(),
            restaurantId: $restaurantId->value(),
            expiresAt: new \DateTimeImmutable('+1 hour'),
            usedAt: new \DateTimeImmutable('-5 minutes'),
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }

    private function makeProduct(): Product
    {
        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create('/img.png'),
            name: ProductName::create('Test Product'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
        );
        $product->pullDomainEvents(); // drain ProductCreated (repo uses fromPersistence in production)
        return $product;
    }
}
