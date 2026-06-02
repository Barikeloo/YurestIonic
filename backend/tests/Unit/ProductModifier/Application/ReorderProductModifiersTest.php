<?php

namespace Tests\Unit\ProductModifier\Application;

use App\ProductModifier\Application\ReorderProductModifiers\ReorderProductModifiers;
use App\ProductModifier\Application\ReorderProductModifiers\ReorderProductModifiersCommand;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Exception\ProductModifierNotFoundException;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class ReorderProductModifiersTest extends TestCase
{
    private ProductModifierRepositoryInterface&Mockery\MockInterface $modifierRepository;
    private TransactionManagerInterface&Mockery\MockInterface $transactionManager;
    private ReorderProductModifiers $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->useCase = new ReorderProductModifiers(
            $this->modifierRepository,
            $this->transactionManager,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_reorders_modifiers(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';
        $modifier1Id = '550e8400-e29b-41d4-a716-446655440001';
        $modifier2Id = '550e8400-e29b-41d4-a716-446655440002';

        $modifier1 = ProductModifier::fromPersistence(
            id: $modifier1Id,
            productId: $productId,
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 0,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        $modifier2 = ProductModifier::fromPersistence(
            id: $modifier2Id,
            productId: $productId,
            name: 'Patatas fritas',
            type: 'accompaniment',
            isRequired: true,
            selectionType: 'single',
            price: 0,
            active: true,
            sortOrder: 1,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        $command = new ReorderProductModifiersCommand(
            productId: $productId,
            items: [
                ['id' => $modifier1Id, 'sort_order' => 2],
                ['id' => $modifier2Id, 'sort_order' => 1],
            ],
        );

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                $callback();
            });

        $this->modifierRepository->shouldReceive('findById')
            ->with($modifier1Id)
            ->once()
            ->andReturn($modifier1);

        $this->modifierRepository->shouldReceive('findById')
            ->with($modifier2Id)
            ->once()
            ->andReturn($modifier2);

        $this->modifierRepository->shouldReceive('save')
            ->twice();

        ($this->useCase)($command);

        $this->assertSame(2, $modifier1->sortOrder());
        $this->assertSame(1, $modifier2->sortOrder());
    }

    public function test_invoke_throws_when_modifier_not_found(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';
        $command = new ReorderProductModifiersCommand(
            productId: $productId,
            items: [
                ['id' => '550e8400-e29b-41d4-a716-446655440001', 'sort_order' => 1],
            ],
        );

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                $callback();
            });

        $this->modifierRepository->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->modifierRepository->shouldNotReceive('save');

        $this->expectException(ProductModifierNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_invoke_throws_when_modifier_belongs_to_another_product(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';
        $otherProductId = '550e8400-e29b-41d4-a716-446655440999';
        $modifierId = '550e8400-e29b-41d4-a716-446655440001';

        $modifier = ProductModifier::fromPersistence(
            id: $modifierId,
            productId: $otherProductId,
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 0,
            createdAt: new \DateTimeImmutable,
            updatedAt: new \DateTimeImmutable,
        );

        $command = new ReorderProductModifiersCommand(
            productId: $productId,
            items: [
                ['id' => $modifierId, 'sort_order' => 1],
            ],
        );

        $this->transactionManager->shouldReceive('run')
            ->once()
            ->andReturnUsing(function (callable $callback) {
                $callback();
            });

        $this->modifierRepository->shouldReceive('findById')
            ->once()
            ->with($modifierId)
            ->andReturn($modifier);

        $this->modifierRepository->shouldNotReceive('save');

        $this->expectException(ProductModifierNotFoundException::class);

        ($this->useCase)($command);
    }
}
