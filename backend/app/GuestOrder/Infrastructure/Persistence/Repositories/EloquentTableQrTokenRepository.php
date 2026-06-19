<?php

declare(strict_types=1);

namespace App\GuestOrder\Infrastructure\Persistence\Repositories;

use App\GuestOrder\Domain\Entity\TableQrToken;
use App\GuestOrder\Domain\Interfaces\TableQrTokenRepositoryInterface;
use App\GuestOrder\Domain\ReadModel\TableStatusData;
use App\GuestOrder\Infrastructure\Persistence\Models\EloquentTableQrToken;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use Illuminate\Support\Facades\DB;

final class EloquentTableQrTokenRepository implements TableQrTokenRepositoryInterface
{
    public function __construct(
        private EloquentTableQrToken $model,
    ) {}

    public function findByToken(string $token): ?TableQrToken
    {
        $model = $this->model->newQuery()
            ->with(['table', 'restaurant'])
            ->where('token', $token)
            ->first();

        return $model !== null ? $this->hydrate($model) : null;
    }

    public function findByTableId(string $tableId): ?TableQrToken
    {
        $table = EloquentTable::query()->where('uuid', $tableId)->first();

        if ($table === null) {
            return null;
        }

        $model = $this->model->newQuery()
            ->with(['table', 'restaurant'])
            ->where('table_id', $table->id)
            ->first();

        return $model !== null ? $this->hydrate($model) : null;
    }

    public function save(TableQrToken $tableQrToken): void
    {
        $table = EloquentTable::query()
            ->where('uuid', $tableQrToken->tableId()->value())
            ->firstOrFail();

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $tableQrToken->id()->value()],
            [
                'table_id'        => $table->id,
                'restaurant_id'   => $table->restaurant_id,
                'token'           => $tableQrToken->token()->value(),
                'catalog_version' => $tableQrToken->catalogVersion(),
                'created_at'      => $tableQrToken->createdAt()->value(),
                'updated_at'      => $tableQrToken->updatedAt()->value(),
            ],
        );
    }

    public function findStatusByToken(string $token): ?TableStatusData
    {
        $row = DB::selectOne("
            SELECT
                r.name              AS restaurant_name,
                t.name              AS table_name,
                z.name              AS zone_name,
                tqt.id              AS qr_token_internal_id,
                t.id                AS table_internal_id,
                o.status            AS order_status
            FROM table_qr_tokens tqt
            INNER JOIN restaurants r ON r.id = tqt.restaurant_id
            INNER JOIN tables      t ON t.id = tqt.table_id AND t.deleted_at IS NULL
            INNER JOIN zones       z ON z.id = t.zone_id
            LEFT  JOIN orders      o ON o.table_id = tqt.table_id
                                    AND o.status IN ('open', 'to-charge')
                                    AND o.deleted_at IS NULL
            WHERE tqt.token = ?
            LIMIT 1
        ", [$token]);

        if ($row === null) {
            return null;
        }

        $activeSessionsCount = (int) DB::selectOne("
            SELECT COUNT(gs.id) AS cnt
            FROM guest_sessions gs
            INNER JOIN table_qr_tokens tqt ON tqt.id = gs.table_qr_token_id
            WHERE tqt.token = ?
              AND gs.expires_at > NOW()
        ", [$token])?->cnt ?? 0;

        $orderStatus = match ($row->order_status) {
            'open'      => 'open',
            'to-charge' => 'to_charge',
            default     => 'none',
        };

        return new TableStatusData(
            restaurantName: $row->restaurant_name,
            restaurantLogoUrl: null,
            restaurantPrimaryColor: null,
            tableName: $row->table_name,
            zoneName: $row->zone_name,
            orderStatus: $orderStatus,
            activeSessionsCount: $activeSessionsCount,
        );
    }

    private function hydrate(EloquentTableQrToken $model): TableQrToken
    {
        return TableQrToken::fromPersistence(
            id: $model->uuid,
            tableId: $model->table->uuid,
            restaurantId: $model->restaurant->uuid,
            token: $model->token,
            catalogVersion: $model->catalog_version,
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }
}
