<?php

namespace Tests\Unit\ProductVariant\Application;

use App\ProductVariant\Application\UpdateProductVariant\UpdateProductVariant;
use App\ProductVariant\Application\UpdateProductVariant\UpdateProductVariantCommand;
use App\ProductVariant\Application\UpdateProductVariant\UpdateProductVariantResponse;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Event\ProductVariantUpdated;
use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateProductVariantTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProductVariantRepositoryInterface&Mockery\MockInterface $variantRepository;
    private EventBusInterface&Mockery\MockInterface $eventBus;
    private UpdateProductVariant $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $this->eventBus          = Mockery::mock(EventBusInterface::class);
        $this->useCase           = new UpdateProductVariant(
            $this->variantRepository,
            $this->eventBus,
        );
    }

    private function makeVariant(string $id = '550e8400-e29b-41d4-a716-446655440000'): ProductVariant
    {
        return ProductVariant::fromPersistence(
            id: $id,
            productId: '550e8400-e29b-41d4-a716-446655440001',
            name: 'Rojo',
            price: 1500,
            stock: 10,
            active: true,
            sortOrder: 1,
            createdAt: new \DateTimeImmutable('-1 day'),
            updatedAt: new \DateTimeImmutable('-1 day'),
        );
    }

    public function test_updates_variant_and_publishes_ProductVariantUpdated(): void
    {
        $variantId = '550e8400-e29b-41d4-a716-446655440000';
        $variant   = $this->makeVariant($variantId);

        $this->variantRepository->shouldReceive('findById')->once()->with($variantId)->andReturn($variant);
        $this->variantRepository->shouldReceive('save')->once()
            ->with(Mockery::on(fn (ProductVariant $v) =>
                $v->name()->value() === 'Azul' && $v->price()->value() === 2000
            ));
        $this->eventBus->shouldReceive('publish')->once()
            ->with(Mockery::on(fn (ProductVariantUpdated $e) =>
                $e->auditBefore()['name'] === 'Rojo'
                && $e->auditAfter()['name'] === 'Azul'
                && $e->auditEntityId() === $variantId
            ));

        $response = ($this->useCase)(new UpdateProductVariantCommand(
            id: $variantId,
            name: 'Azul',
            price: 2000,
            stock: 20,
            active: false,
            sortOrder: 2,
        ));

        $this->assertInstanceOf(UpdateProductVariantResponse::class, $response);
        $this->assertSame('Azul', $response->name);
        $this->assertSame(2000, $response->price);
        $this->assertSame(20, $response->stock);
        $this->assertFalse($response->active);
    }

    public function test_throws_when_variant_not_found(): void
    {
        $this->variantRepository->shouldReceive('findById')->once()->with('missing')->andReturn(null);
        $this->variantRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(ProductVariantNotFoundException::class);

        ($this->useCase)(new UpdateProductVariantCommand(
            id: 'missing',
            name: 'X',
            price: 0,
            stock: 0,
            active: true,
            sortOrder: 0,
        ));
    }
}
