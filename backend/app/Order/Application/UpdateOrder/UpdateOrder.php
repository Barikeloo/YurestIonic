<?php

declare(strict_types=1);

namespace App\Order\Application\UpdateOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateOrderCommand $command): UpdateOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id))
            ?? throw OrderNotFoundException::withId($command->id);

        $dinersBefore = $order->diners()->value();

        if ($command->diners !== null) {
            $order->updateDiners(OrderDiners::create($command->diners));
        }

        $this->orderRepository->save($order);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $order->restaurantId(),
            slug: ActionSlug::create('order.diners_updated'),
            entityType: 'order',
            entityId: $order->id()->value(),
            userId: null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: ['diners' => $dinersBefore],
            after: ['diners' => $order->diners()->value()],
        ));

        return UpdateOrderResponse::create($order);
    }
}
