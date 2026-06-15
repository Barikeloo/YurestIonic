<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Application;

use App\Order\Application\TransferOrder\TransferOrder;
use App\Order\Application\TransferOrder\TransferOrderCommand;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Entity\OrderTransfer;
use App\Order\Domain\Exception\DestinationTableOccupiedException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\SameTableTransferException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderTransferRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Exception\TableNotFoundException;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class TransferOrderTest extends TestCase
{
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private OrderTransferRepositoryInterface&MockInterface $orderTransferRepository;
    private TableRepositoryInterface&MockInterface $tableRepository;
    private TransactionManagerInterface&MockInterface $transactionManager;
    private EventBusInterface&MockInterface $eventBus;
    private TransferOrder $useCase;

    protected function setUp(): void
    {
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->orderTransferRepository = Mockery::mock(OrderTransferRepositoryInterface::class);
        $this->tableRepository = Mockery::mock(TableRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);
        $this->useCase = new TransferOrder(
            $this->orderRepository,
            $this->orderTransferRepository,
            $this->tableRepository,
            $this->transactionManager,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function testTransfersOrderAndPublishesEvent(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $fromTableId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22';
        $toTableId = 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33';

        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            uuid: $orderId,
            status: 'open',
            tableId: $fromTableId,
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

        $this->tableRepository
            ->shouldReceive('findById')
            ->once()
            ->with($toTableId)
            ->andReturn(Mockery::mock(\App\Tables\Domain\Entity\Table::class));

        $this->orderRepository
            ->shouldReceive('findActiveByTableId')
            ->once()
            ->andReturnNull();

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(function (callable $callback) use ($orderId) {
                return $callback();
            });

        $this->orderRepository
            ->shouldReceive('save')
            ->once()
            ->with($order);

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(\App\Order\Domain\Event\OrderTransferred::class));

        $this->orderTransferRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(OrderTransfer::class));

        ($this->useCase)(new TransferOrderCommand(
            orderId: $orderId,
            toTableId: $toTableId,
            transferredByUserId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
        ));

        $this->assertSame($toTableId, $order->tableId()->value());
    }

    public function testThrowsWhenOrderNotFound(): void
    {
        $this->orderRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturnNull();

        $this->expectException(OrderNotFoundException::class);

        ($this->useCase)(new TransferOrderCommand(
            orderId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            toTableId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            transferredByUserId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
        ));
    }

    public function testThrowsWhenTableNotFound(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            uuid: $orderId,
            status: 'open',
            tableId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            openedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            closedByUserId: null,
            diners: 4,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository->shouldReceive('findByUuid')->once()->andReturn($order);
        $this->tableRepository->shouldReceive('findById')->once()->with('c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33')->andReturnNull();

        $this->expectException(TableNotFoundException::class);

        ($this->useCase)(new TransferOrderCommand(
            orderId: $orderId,
            toTableId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33',
            transferredByUserId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
        ));
    }

    public function testThrowsWhenSameTable(): void
    {
        $tableId = 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22';
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            uuid: $orderId,
            status: 'open',
            tableId: $tableId,
            openedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            closedByUserId: null,
            diners: 4,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository->shouldReceive('findByUuid')->once()->andReturn($order);
        $this->tableRepository->shouldReceive('findById')->once()->andReturn(Mockery::mock(\App\Tables\Domain\Entity\Table::class));

        $this->expectException(SameTableTransferException::class);

        ($this->useCase)(new TransferOrderCommand(
            orderId: $orderId,
            toTableId: $tableId,
            transferredByUserId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
        ));
    }

    public function testThrowsWhenDestinationOccupied(): void
    {
        $orderId = 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11';
        $toTableId = 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380c33';

        $order = Order::fromPersistence(
            id: $orderId,
            restaurantId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            uuid: $orderId,
            status: 'open',
            tableId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b22',
            openedByUserId: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            closedByUserId: null,
            diners: 4,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $activeOrder = Order::fromPersistence(
            id: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            restaurantId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380d44',
            uuid: 'e0eebc99-9c0b-4ef8-bb6d-6bb9bd380e55',
            status: 'open',
            tableId: $toTableId,
            openedByUserId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
            closedByUserId: null,
            diners: 2,
            openedAt: new \DateTimeImmutable(),
            closedAt: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
        );

        $this->orderRepository->shouldReceive('findByUuid')->once()->andReturn($order);
        $this->tableRepository->shouldReceive('findById')->once()->andReturn(Mockery::mock(\App\Tables\Domain\Entity\Table::class));
        $this->orderRepository->shouldReceive('findActiveByTableId')->once()->andReturn($activeOrder);

        $this->expectException(DestinationTableOccupiedException::class);

        ($this->useCase)(new TransferOrderCommand(
            orderId: $orderId,
            toTableId: $toTableId,
            transferredByUserId: 'f0eebc99-9c0b-4ef8-bb6d-6bb9bd380f66',
        ));
    }
}
