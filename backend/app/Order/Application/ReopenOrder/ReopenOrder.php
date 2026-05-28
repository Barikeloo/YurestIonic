<?php

declare(strict_types=1);

namespace App\Order\Application\ReopenOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ReopenOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(ReopenOrderCommand $command): ReopenOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id))
            ?? throw OrderNotFoundException::withId($command->id);

        $order->reopen(Uuid::create($command->reopenedByUserId));

        $this->orderRepository->save($order);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $order->restaurantId(),
            slug: ActionSlug::create('order.reopened'),
            entityType: 'order',
            entityId: $order->id()->value(),
            userId: Uuid::create($command->reopenedByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
        ));

        return ReopenOrderResponse::fromOrder($order);
    }
}
