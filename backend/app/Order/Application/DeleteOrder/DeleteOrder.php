<?php

namespace App\Order\Application\DeleteOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(DeleteOrderCommand $command): void
    {
        $orderId = Uuid::create($command->id);
        $order = $this->orderRepository->findByUuid($orderId);

        if ($order === null) {
            throw OrderNotFoundException::withId($command->id);
        }

        if (! $order->status()->isOpen()) {
            throw OrderIsNotOpenException::create();
        }

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $order->restaurantId(),
            slug: ActionSlug::create('order.deleted'),
            entityType: 'order',
            entityId: $order->id()->value(),
            userId: null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: [
                'status' => $order->status()->value(),
                'diners' => $order->diners()->value(),
                'table_id' => $order->tableId()->value(),
            ],
        ));

        $this->orderRepository->delete($order->id());
    }
}
