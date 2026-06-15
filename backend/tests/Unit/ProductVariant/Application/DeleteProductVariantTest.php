<?php

namespace Tests\Unit\ProductVariant\Application;

use App\ProductVariant\Application\DeleteProductVariant\DeleteProductVariant;
use App\ProductVariant\Application\DeleteProductVariant\DeleteProductVariantCommand;
use App\ProductVariant\Domain\Entity\ProductVariant;
use App\ProductVariant\Domain\Event\ProductVariantDeleted;
use App\ProductVariant\Domain\Exception\ProductVariantNotFoundException;
use App\ProductVariant\Domain\Interfaces\ProductVariantRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteProductVariantTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProductVariantRepositoryInterface&Mockery\MockInterface $variantRepository;
    private EventBusInterface&Mockery\MockInterface $eventBus;
    private DeleteProductVariant $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->variantRepository = Mockery::mock(ProductVariantRepositoryInterface::class);
        $this->eventBus          = Mockery::mock(EventBusInterface::class);
        $this->useCase           = new DeleteProductVariant(
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

    public function test_deletes_variant_and_publishes_ProductVariantDeleted(): void
    {
        $variantId = '550e8400-e29b-41d4-a716-446655440000';
        $variant   = $this->makeVariant($variantId);

        $this->variantRepository->shouldReceive('findById')->once()->with($variantId)->andReturn($variant);
        $this->variantRepository->shouldReceive('deleteById')->once()->with($variantId);
        $this->eventBus->shouldReceive('publish')->once()
            ->with(Mockery::on(fn (ProductVariantDeleted $e) =>
                $e->auditEntityId() === $variantId
                && $e->auditBefore()['name'] === 'Rojo'
                && $e->auditBefore()['price'] === 1500
            ));

        ($this->useCase)(new DeleteProductVariantCommand(id: $variantId));

        $this->addToAssertionCount(1);
    }

    public function test_throws_when_variant_not_found(): void
    {
        $this->variantRepository->shouldReceive('findById')->once()->with('missing')->andReturn(null);
        $this->variantRepository->shouldNotReceive('deleteById');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(ProductVariantNotFoundException::class);

        ($this->useCase)(new DeleteProductVariantCommand(id: 'missing'));
    }
}
