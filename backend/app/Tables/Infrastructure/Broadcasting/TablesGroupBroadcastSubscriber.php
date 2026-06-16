<?php

declare(strict_types=1);

namespace App\Tables\Infrastructure\Broadcasting;

use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;
use App\Tables\Domain\Event\TablesMerged;
use App\Tables\Domain\Event\TablesUnmerged;

final class TablesGroupBroadcastSubscriber implements EventSubscriber
{
    public function subscribedTo(): array
    {
        return [
            TablesMerged::class,
            TablesUnmerged::class,
        ];
    }

    public function handle(DomainEvent $event): void
    {
        $broadcast = match (true) {
            $event instanceof TablesMerged => new TableStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'table.merged',
                groupId: $event->auditEntityId(),
            ),
            $event instanceof TablesUnmerged => new TableStatusChanged(
                restaurantId: $event->restaurantId(),
                eventType: 'table.unmerged',
                groupId: $event->auditEntityId(),
            ),
            default => null,
        };

        if ($broadcast !== null) {
            event($broadcast);
        }
    }
}
