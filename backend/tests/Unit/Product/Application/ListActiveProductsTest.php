<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\ListActiveProducts\ListActiveProducts;
use App\Product\Application\ListActiveProducts\ListActiveProductsCommand;
use App\Product\Application\ListProducts\ListProducts;
use App\Product\Application\ListProducts\ListProductsResponse;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListActiveProductsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_delegates_to_list_products_with_active_filter(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);
        $variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);

        $listProducts = new ListProducts($repository, $variantRepository, $modifierRepository);

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([]);

        $variantRepository->shouldNotReceive('findByProductId');
        $modifierRepository->shouldNotReceive('findByProductId');

        $useCase = new ListActiveProducts($listProducts);

        $response = $useCase(new ListActiveProductsCommand());

        $this->assertInstanceOf(ListProductsResponse::class, $response);
        $this->assertEmpty($response->toArray()['items']);
    }
}
