<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\DeleteOrder\DeleteOrder;
use App\Order\Application\DeleteOrder\DeleteOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderDeleted;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class DeleteOrderTest extends TestCase
{
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private EventBusInterface&MockInterface $eventBus;
    private DeleteOrder $useCase;

    protected function setUp(): void
    {
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new DeleteOrder($this->orderRepository, $this->eventBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testDeletesOrderAndPublishesEvent(): void
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
            ->with(Mockery::type(OrderDeleted::class));

        ($this->useCase)(new DeleteOrderCommand(id: $orderId));

        $this->assertNotNull($order->deletedAt());
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

        ($this->useCase)(new DeleteOrderCommand(id: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11'));
    }

    public function testThrowsWhenOrderNotOpen(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            uuid: $orderId,
            status: 'cancelled',
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

        $this->orderRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(OrderIsNotOpenException::class);

        ($this->useCase)(new DeleteOrderCommand(id: $orderId));
    }
}
