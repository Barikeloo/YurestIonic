<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Family\Domain\Entity\Family;
use App\Family\Domain\Interfaces\FamilyRepositoryInterface;
use App\Order\Application\AddLineToOrder\AddLineToOrder;
use App\Order\Application\AddLineToOrder\AddLineToOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Event\OrderLineAdded;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AddLineToOrderTest extends TestCase
{
    private OrderLineRepositoryInterface&MockInterface $orderLineRepository;
    private ProductRepositoryInterface&MockInterface $productRepository;
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private TaxRepositoryInterface&MockInterface $taxRepository;
    private FamilyRepositoryInterface&MockInterface $familyRepository;
    private EventBusInterface&MockInterface $eventBus;
    private AddLineToOrder $useCase;

    protected function setUp(): void
    {
        $this->orderLineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->taxRepository = Mockery::mock(TaxRepositoryInterface::class);
        $this->familyRepository = Mockery::mock(FamilyRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new AddLineToOrder(
            $this->orderLineRepository,
            $this->productRepository,
            $this->orderRepository,
            $this->taxRepository,
            $this->familyRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testAddsNewLineAndPublishesEvent(): void
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

        $product = Mockery::mock(Product::class);

        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($order);

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

        $family = Mockery::mock(Family::class);
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
            ->with(Mockery::type(OrderLine::class));

        $this->productRepository
            ->shouldReceive('save')
            ->once()
            ->with($product);

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderLineAdded::class));

        ($this->useCase)(new AddLineToOrderCommand(
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            orderId: $orderId,
            productId: $productId,
            userId: 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380b88',
            quantity: 2,
            dinerNumber: null,
        ));
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturnNull();

        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(OrderNotFoundException::class);

        ($this->useCase)(new AddLineToOrderCommand(
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            orderId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            productId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            userId: 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380b88',
            quantity: 1,
            dinerNumber: null,
        ));
    }

    public function testThrowsWhenOrderNotOpen(): void
    {
        $order = Order::fromPersistence(
            id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            uuid: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            status: 'cancelled',
            tableId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            openedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            closedByUserId: null,
            diners: 4,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository->shouldReceive('findByUuid')->once()->andReturn($order);
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(OrderIsNotOpenException::class);

        ($this->useCase)(new AddLineToOrderCommand(
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            orderId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            productId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            userId: 'b1eebc99-9c0b-4ef8-bb6d-6bb9bd380b88',
            quantity: 1,
            dinerNumber: null,
        ));
    }
}
