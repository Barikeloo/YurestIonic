<?php

namespace Tests\Unit\Product\Application;

use App\Product\Application\GetProduct\GetProduct;
use App\Product\Application\GetProduct\GetProductCommand;
use App\Product\Application\GetProduct\GetProductResponse;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Product\Domain\ValueObject\ProductImageSrc;
use App\Product\Domain\ValueObject\ProductName;
use App\Product\Domain\ValueObject\ProductPrice;
use App\Product\Domain\ValueObject\ProductStock;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetProductTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_product_when_found(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $product = Product::dddCreate(
            familyId: Uuid::generate(),
            taxId: Uuid::generate(),
            imageSrc: ProductImageSrc::create('/images/coke.png'),
            name: ProductName::create('Coca Cola'),
            price: ProductPrice::create(250),
            stock: ProductStock::create(10),
        );

        $repository->shouldReceive('findById')
            ->once()
            ->with($product->id()->value())
            ->andReturn($product);

        $useCase = new GetProduct($repository);

        $response = $useCase(new GetProductCommand(id: $product->id()->value()));

        $this->assertInstanceOf(GetProductResponse::class, $response);
        $this->assertSame('Coca Cola', $response->name);
        $this->assertSame(250, $response->price);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ProductRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $useCase = new GetProduct($repository);

        $this->expectException(ProductNotFoundException::class);

        $useCase(new GetProductCommand(id: 'non-existent-id'));
    }
}
