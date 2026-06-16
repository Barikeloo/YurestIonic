<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Broadcasting;

use App\Order\Domain\Event\OrderCancelled;
use App\Order\Domain\Event\OrderComandaSent;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Event\OrderDeleted;
use App\Order\Domain\Event\OrderInvoiced;
use App\Order\Domain\Event\OrderLineAdded;
use App\Order\Domain\Event\OrderLineRemoved;
use App\Order\Domain\Event\OrderMarkedToCharge;
use App\Order\Domain\Event\OrderReopened;
use App\Order\Domain\Event\OrderTransferred;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;

final class TablesBroadcastSubscriber implements EventSubscriber
{
    public function subscribedTo(): array
    {
        return [
            OrderCreated::class,
            OrderCancelled::class,
            OrderDeleted::class,
            OrderMarkedToCharge::class,
            OrderReopened::class,
            OrderTransferred::class,
            OrderLineAdded::class,
            OrderLineRemoved::class,
            OrderComandaSent::class,
            OrderInvoiced::class,
        ];
    }

    public function handle(DomainEvent $event): void
    {
        $broadcast = match (true) {
            $event instanceof OrderCreated => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.created',
                orderId: $event->auditEntityId(),
                tableId: $event->auditMetadata()['table_id'],
            ),
            $event instanceof OrderMarkedToCharge => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.marked_to_charge',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderCancelled => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.cancelled',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderReopened => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.reopened',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderTransferred => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.transferred',
                orderId: $event->auditEntityId(),
                fromTableId: $event->auditMetadata()['from_table_id'],
                toTableId: $event->auditMetadata()['to_table_id'],
            ),
            $event instanceof OrderDeleted => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.deleted',
                orderId: $event->auditEntityId(),
                tableId: $event->auditBefore()['table_id'] ?? null,
            ),
            $event instanceof OrderLineAdded => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.line_added',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderLineRemoved => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.line_removed',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderComandaSent => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.comanda_sent',
                orderId: $event->auditEntityId(),
            ),
            $event instanceof OrderInvoiced => new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'order.invoiced',
                orderId: $event->auditEntityId(),
            ),
            default => null,
        };

        if ($broadcast !== null) {
            event($broadcast);
        }
    }
}
