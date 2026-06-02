<?php

namespace Tests\Unit\ProductModifier\Application;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductModifier\Application\ListProductModifiers\ListProductModifiers;
use App\ProductModifier\Application\ListProductModifiers\ListProductModifiersCommand;
use App\ProductModifier\Application\ListProductModifiers\ListProductModifiersResponse;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListProductModifiersTest extends TestCase
{
    private ProductRepositoryInterface&Mockery\MockInterface $productRepository;
    private ProductModifierRepositoryInterface&Mockery\MockInterface $modifierRepository;
    private ListProductModifiers $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);
        $this->useCase = new ListProductModifiers(
            $this->productRepository,
            $this->modifierRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_returns_list_of_modifiers(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';

        $modifier1 = ProductModifier::fromPersistence(
            id: '550e8400-e29b-41d4-a716-446655440001',
            productId: $productId,
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 1,
            createdAt: new \DateTimeImmutable('2025-01-01'),
            updatedAt: new \DateTimeImmutable('2025-01-01'),
        );

        $modifier2 = ProductModifier::fromPersistence(
            id: '550e8400-e29b-41d4-a716-446655440002',
            productId: $productId,
            name: 'Patatas fritas',
            type: 'accompaniment',
            isRequired: true,
            selectionType: 'multi',
            price: 0,
            active: true,
            sortOrder: 2,
            createdAt: new \DateTimeImmutable('2025-01-02'),
            updatedAt: new \DateTimeImmutable('2025-01-02'),
        );

        $command = new ListProductModifiersCommand(productId: $productId);

        $this->productRepository->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn(Mockery::mock(\App\Product\Domain\Entity\Product::class));

        $this->modifierRepository->shouldReceive('findByProductId')
            ->once()
            ->with($productId)
            ->andReturn([$modifier1, $modifier2]);

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(ListProductModifiersResponse::class, $response);
        $this->assertCount(2, $response->modifiers);
        $this->assertSame('Extra queso', $response->modifiers[0]['name']);
        $this->assertSame('Patatas fritas', $response->modifiers[1]['name']);
    }

    public function test_invoke_returns_empty_array_when_no_modifiers(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';
        $command = new ListProductModifiersCommand(productId: $productId);

        $this->productRepository->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn(Mockery::mock(\App\Product\Domain\Entity\Product::class));

        $this->modifierRepository->shouldReceive('findByProductId')
            ->once()
            ->with($productId)
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(ListProductModifiersResponse::class, $response);
        $this->assertCount(0, $response->modifiers);
    }

    public function test_invoke_throws_when_product_not_found(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';
        $command = new ListProductModifiersCommand(productId: $productId);

        $this->productRepository->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn(null);

        $this->modifierRepository->shouldNotReceive('findByProductId');

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)($command);
    }
}
