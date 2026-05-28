<?php

namespace App\Order\Application\TransferOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Entity\OrderTransfer;
use App\Order\Domain\Exception\DestinationTableOccupiedException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Exception\SameTableTransferException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderTransferRepositoryInterface;
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
        private AuditRecorderInterface $auditRecorder,
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

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $order->restaurantId(),
            slug: ActionSlug::create('order.transferred'),
            entityType: 'order',
            entityId: $order->id()->value(),
            userId: Uuid::create($command->transferredByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'from_table_id' => $transfer->fromTableId()->value(),
                'to_table_id' => $transfer->toTableId()->value(),
            ],
        ));

        return TransferOrderResponse::create(
            transferId: $transfer->id()->value(),
            orderId: $transfer->orderId()->value(),
            fromTableId: $transfer->fromTableId()->value(),
            toTableId: $transfer->toTableId()->value(),
            transferredAt: $transfer->transferredAt()->format(DateTimeInterface::ATOM),
        );
    }
}
