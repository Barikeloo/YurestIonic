<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Broadcasting;

use App\GuestOrder\Domain\Event\CheckRequestedByGuest;
use App\GuestOrder\Domain\Event\GuestRoundSubmitted;
use App\GuestOrder\Domain\Event\TableOpenedByGuest;
use App\Order\Infrastructure\Broadcasting\OrderStatusChanged;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;

final class GuestOrderBroadcastSubscriber implements EventSubscriber
{
    public function subscribedTo(): array
    {
        return [
            TableOpenedByGuest::class,
            GuestRoundSubmitted::class,
            CheckRequestedByGuest::class,
        ];
    }

    public function handle(DomainEvent $event): void
    {
        match (true) {
            $event instanceof TableOpenedByGuest => event(new OrderStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'guest.table_opened',
                orderId: $event->orderId(),
                tableId: $event->tableQrTokenId(),
            )),
            $event instanceof GuestRoundSubmitted => $this->handleRoundSubmitted($event),
            $event instanceof CheckRequestedByGuest => $this->handleCheckRequested($event),
            default => null,
        };
    }

    private function handleRoundSubmitted(GuestRoundSubmitted $event): void
    {
        event(new OrderStatusChanged(
            restaurantId: $event->restaurantId(),
            eventType: 'guest.round_submitted',
            orderId: $event->orderId(),
        ));

        event(new GuestOrderActivityBroadcast(
            orderId: $event->orderId(),
            eventType: 'round_submitted',
            guestName: $event->guestName(),
            roundNumber: $event->roundNumber(),
        ));
    }

    private function handleCheckRequested(CheckRequestedByGuest $event): void
    {
        event(new GuestCheckRequestedBroadcast(
            restaurantId: $event->restaurantId(),
            orderId: $event->orderId(),
            tableId: $event->tableId(),
            guestName: $event->guestName(),
            requestedAt: $event->requestedAt(),
        ));

        event(new GuestOrderActivityBroadcast(
            orderId: $event->orderId(),
            eventType: 'check_requested',
            guestName: $event->guestName(),
        ));
    }
}
