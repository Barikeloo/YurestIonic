<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\GetProductPhotoUploadContext\GetProductPhotoUploadContext;
use App\Product\Application\GetProductPhotoUploadContext\GetProductPhotoUploadContextCommand;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenAlreadyUsedException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenExpiredException;
use App\Product\Domain\Exception\ProductPhotoUploadTokenNotFoundException;
use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Restaurant\Domain\Entity\Restaurant;
use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Restaurant\Domain\ValueObject\RestaurantName;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetProductPhotoUploadContextTest extends TestCase
{
    private ProductRepositoryInterface&MockInterface $productRepo;
    private ProductPhotoUploadTokenRepositoryInterface&MockInterface $tokenRepo;
    private RestaurantRepositoryInterface&MockInterface $restaurantRepo;
    private GetProductPhotoUploadContext $useCase;

    protected function setUp(): void
    {
        $this->productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $this->tokenRepo = Mockery::mock(ProductPhotoUploadTokenRepositoryInterface::class);
        $this->restaurantRepo = Mockery::mock(RestaurantRepositoryInterface::class);
        $this->useCase = new GetProductPhotoUploadContext(
            $this->tokenRepo,
            $this->productRepo,
            $this->restaurantRepo,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_valid_token_returns_context(): void
    {
        $restaurantId = Uuid::generate();
        $productId = Uuid::generate();
        $token = $this->makeUsableToken($productId, $restaurantId);
        $product = $this->makeProduct($productId);
        $restaurant = $this->makeRestaurant($restaurantId);

        $this->tokenRepo->shouldReceive('findByToken')->with('aabb')->andReturn($token);
        $this->productRepo->shouldReceive('findByIdAndRestaurant')
            ->with($productId->value(), $restaurantId->value())
            ->andReturn($product);
        $this->restaurantRepo->shouldReceive('findById')
            ->with(Mockery::on(fn(Uuid $id) => $id->value() === $restaurantId->value()))
            ->andReturn($restaurant);

        $response = ($this->useCase)(new GetProductPhotoUploadContextCommand(token: 'aabb'));

        $this->assertSame('Test Product', $response->productName);
        $this->assertSame('/img.png', $response->imageSrc);
        $this->assertSame('Test Restaurant', $response->restaurantName);
    }

    public function test_unknown_token_throws_not_found(): void
    {
        $this->tokenRepo->shouldReceive('findByToken')->with('nonexistent')->andReturn(null);

        $this->expectException(ProductPhotoUploadTokenNotFoundException::class);

        ($this->useCase)(new GetProductPhotoUploadContextCommand(token: 'nonexistent'));
    }

    public function test_expired_token_throws_expired(): void
    {
        $restaurantId = Uuid::generate();
        $productId = Uuid::generate();
        $token = $this->makeExpiredToken($productId, $restaurantId);

        $this->tokenRepo->shouldReceive('findByToken')->with('expired-token')->andReturn($token);

        $this->expectException(ProductPhotoUploadTokenExpiredException::class);

        ($this->useCase)(new GetProductPhotoUploadContextCommand(token: 'expired-token'));
    }

    public function test_used_token_throws_already_used(): void
    {
        $restaurantId = Uuid::generate();
        $productId = Uuid::generate();
        $token = $this->makeUsedToken($productId, $restaurantId);

        $this->tokenRepo->shouldReceive('findByToken')->with('used-token')->andReturn($token);

        $this->expectException(ProductPhotoUploadTokenAlreadyUsedException::class);

        ($this->useCase)(new GetProductPhotoUploadContextCommand(token: 'used-token'));
    }

    public function test_product_not_found_throws_product_not_found(): void
    {
        $restaurantId = Uuid::generate();
        $productId = Uuid::generate();
        $token = $this->makeUsableToken($productId, $restaurantId);

        $this->tokenRepo->shouldReceive('findByToken')->with('valid-token')->andReturn($token);
        $this->productRepo->shouldReceive('findByIdAndRestaurant')
            ->with($productId->value(), $restaurantId->value())
            ->andReturn(null);
        $this->restaurantRepo->shouldReceive('findById')->andReturn(null);

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)(new GetProductPhotoUploadContextCommand(token: 'valid-token'));
    }

    public function test_context_without_restaurant_returns_empty_restaurant_name(): void
    {
        $restaurantId = Uuid::generate();
        $productId = Uuid::generate();
        $token = $this->makeUsableToken($productId, $restaurantId);
        $product = $this->makeProduct($productId);

        $this->tokenRepo->shouldReceive('findByToken')->with('valid')->andReturn($token);
        $this->productRepo->shouldReceive('findByIdAndRestaurant')
            ->with($productId->value(), $restaurantId->value())
            ->andReturn($product);
        $this->restaurantRepo->shouldReceive('findById')->andReturn(null);

        $response = ($this->useCase)(new GetProductPhotoUploadContextCommand(token: 'valid'));

        $this->assertSame('', $response->restaurantName);
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

    private function makeProduct(Uuid $productId): Product
    {
        return Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create('/img.png'),
            name: ProductName::create('Test Product'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
        );
    }

    private function makeRestaurant(Uuid $restaurantId): Restaurant
    {
        return Restaurant::fromPersistence(
            id: Uuid::generate()->value(),
            uuid: $restaurantId->value(),
            name: 'Test Restaurant',
            legalName: 'Test Restaurant S.L.',
            taxId: 'B12345678',
            email: 'test@restaurant.com',
            password: 'hashed',
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );
    }
}
