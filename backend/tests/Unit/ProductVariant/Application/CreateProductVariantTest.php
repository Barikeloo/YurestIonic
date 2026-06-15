<?php

namespace Tests\Unit\ProductVariant\Application;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductVariant\Application\CreateProductVariant\CreateProductVariant;
use App\ProductVariant\Application\CreateProductVariant\CreateProductVariantCommand;
use App\ProductVariant\Application\CreateProductVariant\CreateProductVariantResponse;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Event\ProductVariantCreated;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateProductVariantTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProductRepositoryInterface&Mockery\MockInterface $productRepository;
    private ProductVariantRepositoryInterface&Mockery\MockInterface $variantRepository;
    private EventBusInterface&Mockery\MockInterface $eventBus;
    private CreateProductVariant $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $this->eventBus          = Mockery::mock(EventBusInterface::class);
        $this->useCase           = new CreateProductVariant(
            $this->productRepository,
            $this->variantRepository,
            $this->eventBus,
        );
    }

    public function test_creates_variant_and_publishes_ProductVariantCreated(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';

        $this->productRepository->shouldReceive('findById')->once()->with($productId)
            ->andReturn(Mockery::mock(\App\Product\Domain\Entity\Product::class));

        $this->variantRepository->shouldReceive('save')->once()
            ->with(Mockery::on(fn (ProductVariant $v) =>
                $v->name()->value() === 'Rojo' && $v->price()->value() === 1500
            ));

        $this->eventBus->shouldReceive('publish')->once()
            ->with(Mockery::type(ProductVariantCreated::class));

        $response = ($this->useCase)(new CreateProductVariantCommand(
            productId: $productId,
            name: 'Rojo',
            price: 1500,
            stock: 10,
            active: true,
            sortOrder: 1,
        ));

        $this->assertInstanceOf(CreateProductVariantResponse::class, $response);
        $this->assertSame($productId, $response->productId);
        $this->assertSame('Rojo', $response->name);
        $this->assertSame(1500, $response->price);
        $this->assertSame(10, $response->stock);
        $this->assertTrue($response->active);
    }

    public function test_throws_when_product_not_found(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';

        $this->productRepository->shouldReceive('findById')->once()->with($productId)->andReturn(null);
        $this->variantRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)(new CreateProductVariantCommand(
            productId: $productId,
            name: 'Rojo',
            price: 1500,
            stock: 10,
            active: true,
            sortOrder: 0,
        ));
    }
}
