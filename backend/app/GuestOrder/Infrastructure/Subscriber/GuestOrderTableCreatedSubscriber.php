<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Subscriber;

use App\GuestOrder\Domain\Entity\TableQrToken;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Event\TableCreated;
use Illuminate\Support\Facades\DB;

final class GuestOrderTableCreatedSubscriber implements EventSubscriber
{
    public function __construct(
        private readonly TableQrTokenRepositoryInterface $tableQrTokenRepository,
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

        if ($this->tableQrTokenRepository->findByTableId($tableId) !== null) {
            return;
        }

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

        $qrToken = TableQrToken::dddCreate(
            tableId: Uuid::create($tableId),
            restaurantId: Uuid::create($row->restaurant_uuid),
        );

        $this->tableQrTokenRepository->save($qrToken);
        $qrToken->pullDomainEvents();
    }
}
