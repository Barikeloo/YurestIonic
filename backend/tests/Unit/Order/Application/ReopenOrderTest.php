<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\ReopenOrder\ReopenOrder;
use App\Order\Application\ReopenOrder\ReopenOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderReopened;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ReopenOrderTest extends TestCase
{
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private EventBusInterface&MockInterface $eventBus;
    private ReopenOrder $useCase;

    protected function setUp(): void
    {
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new ReopenOrder($this->orderRepository, $this->eventBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testReopensToChargeOrderAndPublishesEvent(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            uuid: $orderId,
            status: 'to-charge',
            tableId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            openedByUserId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            closedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            diners: 4,
            openedAt: new \DateTimeImmutable(),
            closedAt: new \DateTimeImmutable(),
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
            ->with(Mockery::type(OrderReopened::class));

        ($this->useCase)(new ReopenOrderCommand(
            id: $orderId,
            reopenedByUserId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
        ));

        $this->assertTrue($order->status()->isOpen());
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

        ($this->useCase)(new ReopenOrderCommand(
            id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            reopenedByUserId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
        ));
    }
}
