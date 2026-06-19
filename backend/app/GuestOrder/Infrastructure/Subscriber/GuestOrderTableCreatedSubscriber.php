<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Subscriber;

use App\GuestOrder\Application\GenerateTableQrToken\GenerateTableQrToken;
use App\GuestOrder\Application\GenerateTableQrToken\GenerateTableQrTokenCommand;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;
use App\Tables\Domain\Event\TableCreated;
use Illuminate\Support\Facades\DB;

final class GuestOrderTableCreatedSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly GenerateTableQrToken $generateTableQrToken,
    ) {}

    public function subscribedTo(): array
    {
        return [TableCreated::class];
    }

    public function handle(DomainEvent $event): void
    {
        if (! $event instanceof TableCreated) {
            return;
        }

        $tableId = $event->auditEntityId();

        // Direct query to avoid HasTenantScope on EloquentZone (no tenant context in event handlers)
        $row = DB::table('tables')
            ->join('zones', 'zones.id', '=', 'tables.zone_id')
            ->join('restaurants', 'restaurants.id', '=', 'zones.restaurant_id')
            ->where('tables.uuid', $tableId)
            ->whereNull('tables.deleted_at')
            ->select(['restaurants.uuid as restaurant_uuid'])
            ->first();

        if ($row === null) {
            return;
        }

        ($this->generateTableQrToken)(new GenerateTableQrTokenCommand(
            tableId: $tableId,
            restaurantId: $row->restaurant_uuid,
        ));
    }
}
