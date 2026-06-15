<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\CreateOrder\CreateOrder;
use App\Order\Application\CreateOrder\CreateOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Exception\TableAlreadyHasOpenOrderException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CreateOrderTest extends TestCase
{
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private EventBusInterface&MockInterface $eventBus;
    private CreateOrder $useCase;

    protected function setUp(): void
    {
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new CreateOrder($this->orderRepository, $this->eventBus);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testCreatesOrderAndPublishesEvent(): void
    {
        $tableId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';

        $this->orderRepository
            ->shouldReceive('findByTableId')
            ->once()
            ->with(Mockery::on(fn (Uuid $uuid) => $uuid->value() === $tableId))
            ->andReturnNull();

        $this->orderRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(Order::class));

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(OrderCreated::class));

        $response = ($this->useCase)(new CreateOrderCommand(
            restaurantId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            tableId: $tableId,
            openedByUserId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            diners: 4,
        ));

        $this->assertNotNull($response->id);
    }

    public function testThrowsWhenTableAlreadyHasOpenOrder(): void
    {
        $tableId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $existingOrder = Order::fromPersistence(
            id: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            restaurantId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            uuid: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            status: 'open',
            tableId: $tableId,
            openedByUserId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            closedByUserId: null,
            diners: 2,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository
            ->shouldReceive('findByTableId')
            ->once()
            ->andReturn($existingOrder);

        $this->orderRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(TableAlreadyHasOpenOrderException::class);

        ($this->useCase)(new CreateOrderCommand(
            restaurantId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            tableId: $tableId,
            openedByUserId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            diners: 4,
        ));
    }
}
