<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\UpdateOrder\UpdateOrder;
use App\Order\Application\UpdateOrder\UpdateOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderDinersUpdated;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class UpdateOrderTest extends TestCase
{
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private EventBusInterface&MockInterface $eventBus;
    private UpdateOrder $useCase;

    protected function setUp(): void
    {
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new UpdateOrder($this->orderRepository, $this->eventBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testUpdatesDinersAndPublishesEvent(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            uuid: $orderId,
            status: 'open',
            tableId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            openedByUserId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            closedByUserId: null,
            diners: 2,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($order);

        $this->orderRepository
            ->shouldReceive('save')
            ->once()
            ->with($order);

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(fn (OrderDinersUpdated $event): bool => $event->auditBefore() === ['diners' => 2]));

        ($this->useCase)(new UpdateOrderCommand(
            id: $orderId,
            diners: 5,
        ));

        $this->assertSame(5, $order->diners()->value());
    }

    public function testDoesNothingWhenDinersIsNull(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            uuid: $orderId,
            status: 'open',
            tableId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            openedByUserId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            closedByUserId: null,
            diners: 2,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($order);

        $this->orderRepository
            ->shouldReceive('save')
            ->once()
            ->with($order);

        $this->eventBus
            ->shouldReceive('publish')
            ->once();

        ($this->useCase)(new UpdateOrderCommand(
            id: $orderId,
            diners: null,
        ));

        $this->assertSame(2, $order->diners()->value());
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturnNull();

        $this->orderRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(OrderNotFoundException::class);

        ($this->useCase)(new UpdateOrderCommand(
            id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            diners: 5,
        ));
    }
}
