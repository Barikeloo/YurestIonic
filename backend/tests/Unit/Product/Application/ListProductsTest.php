<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\ListProducts\ListProducts;
use App\Product\Application\ListProducts\ListProductsCommand;
use App\Product\Application\ListProducts\ListProductsResponse;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListProductsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_all_products(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);

        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create('/images/coke.png'),
            name: ProductName::create('Coca Cola'),
            price: ProductPrice::create(250),
            stock: ProductStock::create(10),
        );

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([$product]);

        $variantRepository->shouldReceive('findByProductId')
            ->andReturn([]);

        $modifierRepository->shouldReceive('findByProductId')
            ->andReturn([]);

        $useCase = new ListProducts($repository, $variantRepository, $modifierRepository);

        $response = $useCase(new ListProductsCommand(
            includeDeleted: false,
            onlyActive: false,
        ));

        $this->assertInstanceOf(ListProductsResponse::class, $response);
        $this->assertCount(1, $response->toArray()['items']);
        $this->assertSame('Coca Cola', $response->toArray()['items'][0]['name']);
    }

    public function test_filters_only_active(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);

        $activeProduct = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Active'),
            price: ProductPrice::create(100),
            stock: ProductStock::create(5),
        );

        $inactiveProduct = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create(null),
            name: ProductName::create('Inactive'),
            price: ProductPrice::create(200),
            stock: ProductStock::create(0),
            active: false,
        );

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([$activeProduct, $inactiveProduct]);

        $variantRepository->shouldReceive('findByProductId')
            ->andReturn([]);

        $modifierRepository->shouldReceive('findByProductId')
            ->andReturn([]);

        $useCase = new ListProducts($repository, $variantRepository, $modifierRepository);

        $response = $useCase(new ListProductsCommand(
            includeDeleted: false,
            onlyActive: true,
        ));

        $this->assertCount(1, $response->toArray()['items']);
        $this->assertSame('Active', $response->toArray()['items'][0]['name']);
    }

    public function test_returns_empty_list(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([]);

        $variantRepository->shouldNotReceive('findByProductId');
        $modifierRepository->shouldNotReceive('findByProductId');

        $useCase = new ListProducts($repository, $variantRepository, $modifierRepository);

        $response = $useCase(new ListProductsCommand(
            includeDeleted: false,
            onlyActive: false,
        ));

        $this->assertInstanceOf(ListProductsResponse::class, $response);
        $this->assertEmpty($response->toArray()['items']);
    }
}
