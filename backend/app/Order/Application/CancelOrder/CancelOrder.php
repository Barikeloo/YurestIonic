<?php

declare(strict_types=1);

namespace App\Order\Application\CancelOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CancelOrderCommand $command): CancelOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id))
            ?? throw OrderNotFoundException::withId($command->id);

        $order->cancel(Uuid::create($command->cancelledByUserId));

        $this->orderRepository->save($order);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $order->restaurantId(),
            slug: ActionSlug::create('order.cancelled'),
            entityType: 'order',
            entityId: $order->id()->value(),
            userId: Uuid::create($command->cancelledByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
        ));

        return CancelOrderResponse::fromOrder($order);
    }
}
