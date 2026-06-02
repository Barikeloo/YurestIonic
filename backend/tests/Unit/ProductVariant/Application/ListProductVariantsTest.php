<?php

namespace Tests\Unit\ProductVariant\Application;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductVariant\Application\ListProductVariants\ListProductVariants;
use App\ProductVariant\Application\ListProductVariants\ListProductVariantsCommand;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\ProductVariant\Domain\ValueObject\VariantName;
use App\ProductVariant\Domain\ValueObject\VariantPrice;
use App\ProductVariant\Domain\ValueObject\VariantStock;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ListProductVariantsTest extends TestCase
{
    private ProductRepositoryInterface&MockInterface $productRepository;
    private ProductVariantRepositoryInterface&MockInterface $variantRepository;
    private ListProductVariants $useCase;

    protected function setUp(): void
    {
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);

        $this->useCase = new ListProductVariants(
            $this->productRepository,
            $this->variantRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_lists_variants_by_product(): void
    {
        $productId = Uuid::generate()->value();
        $command = new ListProductVariantsCommand(productId: $productId);

        $product = Mockery::mock(Product::class);
        $this->productRepository
            ->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn($product);

        $variant1 = ProductVariant::dddCreate(
            productId: Uuid::create($productId),
            name: VariantName::create('Rojo'),
            price: VariantPrice::create(1500),
            stock: VariantStock::create(10),
        );

        $variant2 = ProductVariant::dddCreate(
            productId: Uuid::create($productId),
            name: VariantName::create('Azul'),
            price: VariantPrice::create(2000),
            stock: VariantStock::create(5),
            active: false,
            sortOrder: 1,
        );

        $this->variantRepository
            ->shouldReceive('findByProductId')
            ->once()
            ->with($productId)
            ->andReturn([$variant1, $variant2]);

        $response = ($this->useCase)($command);

        $this->assertCount(2, $response->variants);
        $this->assertSame('Rojo', $response->variants[0]['name']);
        $this->assertSame('Azul', $response->variants[1]['name']);
    }

    public function test_throws_exception_when_product_not_found(): void
    {
        $productId = Uuid::generate()->value();
        $command = new ListProductVariantsCommand(productId: $productId);

        $this->productRepository
            ->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn(null);

        $this->variantRepository->shouldNotReceive('findByProductId');

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_returns_empty_array_when_no_variants(): void
    {
        $productId = Uuid::generate()->value();
        $command = new ListProductVariantsCommand(productId: $productId);

        $product = Mockery::mock(Product::class);
        $this->productRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($product);

        $this->variantRepository
            ->shouldReceive('findByProductId')
            ->once()
            ->with($productId)
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertEmpty($response->variants);
    }
}
