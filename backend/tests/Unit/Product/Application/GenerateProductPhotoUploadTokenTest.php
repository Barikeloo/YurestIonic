<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\GenerateProductPhotoUploadToken\GenerateProductPhotoUploadToken;
use App\Product\Application\GenerateProductPhotoUploadToken\GenerateProductPhotoUploadTokenCommand;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductPhotoUploadTokenRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GenerateProductPhotoUploadTokenTest extends TestCase
{
    private ProductRepositoryInterface&MockInterface $productRepo;
    private ProductPhotoUploadTokenRepositoryInterface&MockInterface $tokenRepo;
    private GenerateProductPhotoUploadToken $useCase;

    protected function setUp(): void
    {
        $this->productRepo = Mockery::mock(ProductRepositoryInterface::class);
        $this->tokenRepo = Mockery::mock(ProductPhotoUploadTokenRepositoryInterface::class);
        $this->useCase = new GenerateProductPhotoUploadToken($this->productRepo, $this->tokenRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_token_upload_url_and_expiry(): void
    {
        $restaurantId = Uuid::generate()->value();
        $productId = Uuid::generate()->value();
        $product = \App\Product\Domain\Entity\Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Test'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(1),
        );

        $this->productRepo->shouldReceive('findByIdAndRestaurant')
            ->with($productId, $restaurantId)
            ->andReturn($product);

        $this->tokenRepo->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn($token) => $token->token() !== ''));

        $response = ($this->useCase)(new GenerateProductPhotoUploadTokenCommand(
            productId: $productId,
            restaurantId: $restaurantId,
            ttlMinutes: 10,
            uploadBaseUrl: 'http://localhost:4200',
        ));

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $response->token);
        $this->assertStringContainsString('/u/foto/', $response->uploadUrl);
        $this->assertStringContainsString($response->token, $response->uploadUrl);
        $this->assertNotNull($response->expiresAt);
    }

    public function test_throws_not_found_when_product_does_not_exist(): void
    {
        $this->productRepo->shouldReceive('findByIdAndRestaurant')
            ->with('missing', 'rest-1')
            ->andReturn(null);

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)(new GenerateProductPhotoUploadTokenCommand(
            productId: 'missing',
            restaurantId: 'rest-1',
            ttlMinutes: 10,
            uploadBaseUrl: 'http://localhost:4200',
        ));
    }
}
