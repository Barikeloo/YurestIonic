<?php

namespace Tests\Unit\ProductModifier\Application;

use App\Product\Domain\Exception\ProductNotFoundException;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\ProductModifier\Application\CreateProductModifier\CreateProductModifier;
use App\ProductModifier\Application\CreateProductModifier\CreateProductModifierCommand;
use App\ProductModifier\Application\CreateProductModifier\CreateProductModifierResponse;
use App\ProductModifier\Domain\Entity\ProductModifier;
use App\ProductModifier\Domain\Event\ProductModifierCreated;
use App\ProductModifier\Domain\Interfaces\ProductModifierRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateProductModifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProductRepositoryInterface&Mockery\MockInterface $productRepository;
    private ProductModifierRepositoryInterface&Mockery\MockInterface $modifierRepository;
    private EventBusInterface&Mockery\MockInterface $eventBus;
    private CreateProductModifier $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->productRepository  = Mockery::mock(ProductRepositoryInterface::class);
        $this->modifierRepository = Mockery::mock(ProductModifierRepositoryInterface::class);
        $this->eventBus           = Mockery::mock(EventBusInterface::class);
        $this->useCase            = new CreateProductModifier(
            $this->productRepository,
            $this->modifierRepository,
            $this->eventBus,
        );
    }

    public function test_invoke_creates_modifier_and_publishes_event(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';

        $this->productRepository->shouldReceive('findById')
            ->once()->with($productId)
            ->andReturn(Mockery::mock(\App\Product\Domain\Entity\Product::class));

        $this->modifierRepository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (ProductModifier $m) =>
                $m->name()->value() === 'Extra queso' && $m->price()->value() === 200
            ));

        $this->eventBus->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(ProductModifierCreated::class));

        $response = ($this->useCase)(new CreateProductModifierCommand(
            productId: $productId,
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 1,
        ));

        $this->assertInstanceOf(CreateProductModifierResponse::class, $response);
        $this->assertSame('Extra queso', $response->name);
        $this->assertSame('extra', $response->type);
        $this->assertSame(200, $response->price);
    }

    public function test_invoke_throws_when_product_not_found(): void
    {
        $productId = '550e8400-e29b-41d4-a716-446655440000';

        $this->productRepository->shouldReceive('findById')
            ->once()->with($productId)->andReturn(null);

        $this->modifierRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(ProductNotFoundException::class);

        ($this->useCase)(new CreateProductModifierCommand(
            productId: $productId,
            name: 'Extra queso',
            type: 'extra',
            isRequired: false,
            selectionType: 'single',
            price: 200,
            active: true,
            sortOrder: 0,
        ));
    }
}
