<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\DeleteOrderLine\DeleteOrderLine;
use App\Order\Application\DeleteOrderLine\DeleteOrderLineCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Event\OrderLineRemoved;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderLineNotFoundException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class DeleteOrderLineTest extends TestCase
{
    private OrderLineRepositoryInterface&MockInterface $orderLineRepository;
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private ProductRepositoryInterface&MockInterface $productRepository;
    private EventBusInterface&MockInterface $eventBus;
    private DeleteOrderLine $useCase;

    protected function setUp(): void
    {
        $this->orderLineRepository = Mockery::mock(OrderLineRepositoryInterface::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new DeleteOrderLine(
            $this->orderLineRepository,
            $this->orderRepository,
            $this->productRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testDeletesLineAndPublishesEvent(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $lineId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22';

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

        $line = OrderLine::fromPersistence(
            id: $lineId,
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            uuid: $lineId,
            orderId: $orderId,
            productId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
            variantId: null,
            variantName: null,
            modifiers: null,
            menuId: null,
            menuName: null,
            menuSelections: null,
            userId: 'a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a77',
            quantity: 2,
            price: 1500,
            taxPercentage: 21,
            dinerNumber: null,
            discountPercent: null,
            discountAmountCents: null,
            discountReason: null,
            isInvitation: false,
            priceOverrideCents: null,
            notes: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderLineRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($line);

        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($order);

        $this->productRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->orderLineRepository
            ->shouldReceive('delete')
            ->once()
            ->with($line->id());

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderLineRemoved::class));

        ($this->useCase)(new DeleteOrderLineCommand(
            lineId: $lineId,
            userId: 'a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a77',
        ));
    }

    public function testThrowsWhenLineNotFound(): void
    {
        $this->orderLineRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturnNull();

        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(OrderLineNotFoundException::class);

        ($this->useCase)(new DeleteOrderLineCommand(
            lineId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            userId: 'a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a77',
        ));
    }

    public function testThrowsWhenOrderNotOpen(): void
    {
        $lineId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22';

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

        $line = OrderLine::fromPersistence(
            id: $lineId,
            restaurantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            uuid: $lineId,
            orderId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            productId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
            variantId: null,
            variantName: null,
            modifiers: null,
            menuId: null,
            menuName: null,
            menuSelections: null,
            userId: 'a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a77',
            quantity: 1,
            price: 1000,
            taxPercentage: 21,
            dinerNumber: null,
            discountPercent: null,
            discountAmountCents: null,
            discountReason: null,
            isInvitation: false,
            priceOverrideCents: null,
            notes: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderLineRepository->shouldReceive('findByUuid')->once()->andReturn($line);
        $this->orderRepository->shouldReceive('findByUuid')->once()->andReturn($order);
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(OrderIsNotOpenException::class);

        ($this->useCase)(new DeleteOrderLineCommand(
            lineId: $lineId,
            userId: 'a1eebc99-9c0b-4ef8-bb6d-6bb9bd380a77',
        ));
    }
}
