<?php

namespace App\Order\Application\CreateOrder;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\TableAlreadyHasOpenOrderException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CreateOrderCommand $command): CreateOrderResponse
    {
        $tableUuid = Uuid::create($command->tableId);
        if ($this->orderRepository->findByTableId($tableUuid) !== null) {
            throw TableAlreadyHasOpenOrderException::create();
        }

        $order = Order::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            tableId: $tableUuid,
            openedByUserId: Uuid::create($command->openedByUserId),
            diners: OrderDiners::create($command->diners),
        );

        $this->orderRepository->save($order);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('order.created'),
            entityType: 'order',
            entityId: $order->id()->value(),
            userId: Uuid::create($command->openedByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: ['diners' => $command->diners],
        ));

        return CreateOrderResponse::create($order);
    }
}
