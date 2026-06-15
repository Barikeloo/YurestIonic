<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\MarkOrderToCharge\MarkOrderToCharge;
use App\Order\Application\MarkOrderToCharge\MarkOrderToChargeCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderMarkedToCharge;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class MarkOrderToChargeTest extends TestCase
{
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private EventBusInterface&MockInterface $eventBus;
    private MarkOrderToCharge $useCase;

    protected function setUp(): void
    {
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new MarkOrderToCharge($this->orderRepository, $this->eventBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testMarksOrderToChargeAndPublishesEvent(): void
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
            ->andReturn($order);

        $this->orderRepository
            ->shouldReceive('save')
            ->once()
            ->with($order);

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderMarkedToCharge::class));

        ($this->useCase)(new MarkOrderToChargeCommand(
            id: $orderId,
            closedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
        ));

        $this->assertTrue($order->status()->isToCharge());
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

        ($this->useCase)(new MarkOrderToChargeCommand(
            id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            closedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
        ));
    }
}
