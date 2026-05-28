<?php

declare(strict_types=1);

namespace App\Order\Application\MarkOrderToCharge;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class MarkOrderToCharge
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(MarkOrderToChargeCommand $command): MarkOrderToChargeResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id))
            ?? throw OrderNotFoundException::withId($command->id);

        $order->markToCharge(Uuid::create($command->closedByUserId));

        $this->orderRepository->save($order);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $order->restaurantId(),
            slug: ActionSlug::create('order.marked_to_charge'),
            entityType: 'order',
            entityId: $order->id()->value(),
            userId: Uuid::create($command->closedByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
        ));

        return MarkOrderToChargeResponse::fromOrder($order);
    }
}
