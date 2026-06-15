<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\CancelOrder\CancelOrder;
use App\Order\Application\CancelOrder\CancelOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderCancelled;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CancelOrderTest extends TestCase
{
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private EventBusInterface&MockInterface $eventBus;
    private CancelOrder $useCase;

    protected function setUp(): void
    {
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new CancelOrder($this->orderRepository, $this->eventBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCancelsOrderAndPublishesEvent(): void
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
            diners: 4,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(Mockery::on(fn (Uuid $uuid) => $uuid->value() === $orderId))
            ->andReturn($order);

        $this->orderRepository
            ->shouldReceive('save')
            ->once()
            ->with($order);

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderCancelled::class));

        ($this->useCase)(new CancelOrderCommand(
            id: $orderId,
            cancelledByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
        ));

        $this->assertTrue($order->status()->isCancelled());
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturnNull();

        $this->orderRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(OrderNotFoundException::class);

        ($this->useCase)(new CancelOrderCommand(
            id: $orderId,
            cancelledByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
        ));
    }
}
