<?php

namespace Tests\Unit\ProductModifier\Application;

use App\ProductModifier\Application\DeleteProductModifier\DeleteProductModifier;
use App\ProductModifier\Application\DeleteProductModifier\DeleteProductModifierCommand;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Event\ProductModifierDeleted;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteProductModifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProductModifierRepositoryInterface&Mockery\MockInterface $modifierRepository;
    private EventBusInterface&Mockery\MockInterface $eventBus;
    private DeleteProductModifier $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);
        $this->eventBus           = Mockery::mock(EventBusInterface::class);
        $this->useCase            = new DeleteProductModifier(
            $this->modifierRepository,
            $this->eventBus,
        );
    }

    private function makeModifier(string $id = '550e8400-e29b-41d4-a716-446655440000'): ProductModifier
    {
        return ProductModifier::fromPersistence(
            id: $id,
            productId: '550e8400-e29b-41d4-a716-446655440001',
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 1,
            createdAt: new \DateTimeImmutable('-1 day'),
            updatedAt: new \DateTimeImmutable('-1 day'),
        );
    }

    public function test_deletes_modifier_and_publishes_ProductModifierDeleted(): void
    {
        $modifierId = '550e8400-e29b-41d4-a716-446655440000';
        $modifier   = $this->makeModifier($modifierId);

        $this->modifierRepository->shouldReceive('findById')->once()->with($modifierId)->andReturn($modifier);
        $this->modifierRepository->shouldReceive('deleteById')->once()->with($modifierId)->andReturn(true);
        $this->eventBus->shouldReceive('publish')->once()
            ->with(Mockery::on(fn (ProductModifierDeleted $e) =>
                $e->auditEntityId() === $modifierId
                && $e->auditBefore()['name'] === 'Extra queso'
                && $e->auditBefore()['type'] === 'extra'
            ));

        ($this->useCase)(new DeleteProductModifierCommand(id: $modifierId));

        $this->addToAssertionCount(1);
    }

    public function test_throws_when_modifier_not_found(): void
    {
        $this->modifierRepository->shouldReceive('findById')->once()->with('missing')->andReturn(null);
        $this->modifierRepository->shouldNotReceive('deleteById');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(ProductModifierNotFoundException::class);

        ($this->useCase)(new DeleteProductModifierCommand(id: 'missing'));
    }
}
