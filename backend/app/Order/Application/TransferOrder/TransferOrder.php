<?php

namespace App\Order\Application\TransferOrder;

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
use DateTimeInterface;

class TransferOrder
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderTransferRepositoryInterface $orderTransferRepository,
        private TableRepositoryInterface $tableRepository,
        private TransactionManagerInterface $transactionManager,
        private EventBusInterface $eventBus,
    ) {}

    public function __invoke(TransferOrderCommand $command): TransferOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->orderId))
            ?? throw OrderNotFoundException::withId($command->orderId);

        $destinationTable = $this->tableRepository->findById($command->toTableId)
            ?? throw TableNotFoundException::withId($command->toTableId);

        $fromTableId = $order->tableId();

        if ($fromTableId->value() === $command->toTableId) {
            throw SameTableTransferException::create();
        }

        $activeAtDestination = $this->orderRepository->findActiveByTableId(Uuid::create($command->toTableId));
        if ($activeAtDestination !== null && $activeAtDestination->id()->value() !== $order->id()->value()) {
            throw DestinationTableOccupiedException::create();
        }

        $toTableId = Uuid::create($command->toTableId);
        $transferredByUserId = Uuid::create($command->transferredByUserId);

        $transfer = $this->transactionManager->run(function () use ($order, $fromTableId, $toTableId, $transferredByUserId): OrderTransfer {
            $order->transferTo($toTableId);
            $this->orderRepository->save($order);

            $this->eventBus->publish(...$order->pullDomainEvents());

            $newTransfer = OrderTransfer::dddCreate(
                id: Uuid::generate(),
                orderId: $order->id(),
                fromTableId: $fromTableId,
                toTableId: $toTableId,
                transferredByUserId: $transferredByUserId,
            );
            $this->orderTransferRepository->save($newTransfer);

            return $newTransfer;
        });

        return TransferOrderResponse::create(
            transferId: $transfer->id()->value(),
            orderId: $transfer->orderId()->value(),
            fromTableId: $transfer->fromTableId()->value(),
            toTableId: $transfer->toTableId()->value(),
            transferredAt: $transfer->transferredAt()->format(DateTimeInterface::ATOM),
        );
    }
}
