<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Menu\Domain\Interfaces\MenuRepositoryInterface;
use App\Order\Application\BatchAddLinesToOrder\BatchAddLinesToOrder;
use App\Order\Application\BatchAddLinesToOrder\BatchAddLinesToOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderComandaSent;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class BatchAddLinesToOrderTest extends TestCase
{
    private OrderLineRepositoryInterface&MockInterface $orderLineRepository;
    private ProductRepositoryInterface&MockInterface $productRepository;
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private TaxRepositoryInterface&MockInterface $taxRepository;
    private FamilyRepositoryInterface&MockInterface $familyRepository;
    private MenuRepositoryInterface&MockInterface $menuRepository;
    private EventBusInterface&MockInterface $eventBus;
    private BatchAddLinesToOrder $useCase;

    protected function setUp(): void
    {
        $this->orderLineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->taxRepository = Mockery::mock(TaxRepositoryInterface::class);
        $this->familyRepository = Mockery::mock(FamilyRepositoryInterface::class);
        $this->menuRepository = Mockery::mock(MenuRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new BatchAddLinesToOrder(
            $this->orderLineRepository,
            $this->productRepository,
            $this->orderRepository,
            $this->taxRepository,
            $this->familyRepository,
            $this->menuRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testAddsProductLineAndPublishesComandaSent(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $productId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22';

        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            uuid: $orderId,
            status: 'open',
            tableId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            openedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            closedByUserId: null,
            diners: 4,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($order);

        $product = Mockery::mock(\App\Product\Domain\Entity\Product::class);

        $this->productRepository
            ->shouldReceive('findById')
            ->once()
            ->with($productId)
            ->andReturn($product);

        $product->shouldReceive('isActive')->once()->andReturnTrue();
        $product->shouldReceive('familyId')->once()->andReturn(Uuid::create('f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66'));
        $product->shouldReceive('taxId')->once()->andReturn(Uuid::create('a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a77'));
        $product->shouldReceive('price')->once()->andReturn(\App\Product\Domain\ValueObject\ProductPrice::create(1500));
        $product->shouldReceive('name')->once()->andReturn(\App\Product\Domain\ValueObject\ProductName::create('Test Product'));
        $product->shouldReceive('decreaseStock')->once()->with(2);

        $family = Mockery::mock(\App\Family\Domain\Entity\Family::class);
        $family->shouldReceive('isActive')->once()->andReturnTrue();

        $this->familyRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($family);

        $tax = Mockery::mock(\App\Tax\Domain\Entity\Tax::class);
        $tax->shouldReceive('percentage')->once()->andReturn(\App\Tax\Domain\ValueObject\TaxPercentage::create(21));

        $this->taxRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn($tax);

        $this->orderLineRepository
            ->shouldReceive('findMatchingMergeableLine')
            ->once()
            ->andReturnNull();

        $this->orderLineRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(\App\Order\Domain\Entity\OrderLine::class));

        $this->productRepository
            ->shouldReceive('save')
            ->once()
            ->with($product);

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderComandaSent::class));

        ($this->useCase)(new BatchAddLinesToOrderCommand(
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            orderId: $orderId,
            userId: 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380b88',
            productLines: [
                ['product_id' => $productId, 'quantity' => 2],
            ],
            menuLines: [],
        ));
    }
}
