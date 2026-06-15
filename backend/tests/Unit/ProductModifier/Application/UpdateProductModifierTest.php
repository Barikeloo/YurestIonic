<?php

namespace Tests\Unit\ProductModifier\Application;

use App\ProductModifier\Application\UpdateProductModifier\UpdateProductModifier;
use App\ProductModifier\Application\UpdateProductModifier\UpdateProductModifierCommand;
use App\ProductModifier\Application\UpdateProductModifier\UpdateProductModifierResponse;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Event\ProductModifierUpdated;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateProductModifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProductModifierRepositoryInterface&Mockery\MockInterface $modifierRepository;
    private EventBusInterface&Mockery\MockInterface $eventBus;
    private UpdateProductModifier $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);
        $this->eventBus           = Mockery::mock(EventBusInterface::class);
        $this->useCase            = new UpdateProductModifier(
            $this->modifierRepository,
            $this->eventBus,
        );
    }

    private function makeModifier(string $id = '550e8400-e29b-41d4-a716-446655440000'): ProductModifier
    {
        return ProductModifier::fromPersistence(
            id: $id,
            productId: '550e8400-e29b-41d4-a716-446655440001',
            name: 'Patatas fritas',
            type: 'accompaniment',
            isRequired: true,
            selectionType: 'single',
            price: 0,
            active: true,
            sortOrder: 1,
            createdAt: new \DateTimeImmutable('-1 day'),
            updatedAt: new \DateTimeImmutable('-1 day'),
        );
    }

    public function test_updates_modifier_and_publishes_ProductModifierUpdated(): void
    {
        $modifierId = '550e8400-e29b-41d4-a716-446655440000';
        $modifier   = $this->makeModifier($modifierId);

        $this->modifierRepository->shouldReceive('findById')->once()->with($modifierId)->andReturn($modifier);
        $this->modifierRepository->shouldReceive('save')->once()
            ->with(Mockery::on(fn (ProductModifier $m) =>
                $m->name()->value() === 'Aros de cebolla' && $m->price()->value() === 300
            ));
        $this->eventBus->shouldReceive('publish')->once()
            ->with(Mockery::on(fn (ProductModifierUpdated $e) =>
                $e->auditBefore()['name'] === 'Patatas fritas'
                && $e->auditAfter()['name'] === 'Aros de cebolla'
            ));

        $response = ($this->useCase)(new UpdateProductModifierCommand(
            id: $modifierId,
            name: 'Aros de cebolla',
            type: 'extra',
            isRequired: false,
            selectionType: 'multi',
            price: 300,
            active: false,
            sortOrder: 2,
        ));

        $this->assertInstanceOf(UpdateProductModifierResponse::class, $response);
        $this->assertSame('Aros de cebolla', $response->name);
        $this->assertSame(300, $response->price);
    }

    public function test_throws_when_modifier_not_found(): void
    {
        $this->modifierRepository->shouldReceive('findById')->once()->with('missing')->andReturn(null);
        $this->modifierRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(ProductModifierNotFoundException::class);

        ($this->useCase)(new UpdateProductModifierCommand(
            id: 'missing',
            name: 'X',
            type: 'extra',
            isRequired: false,
            selectionType: 'multi',
            price: 0,
            active: true,
            sortOrder: 0,
        ));
    }
}
