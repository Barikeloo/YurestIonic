<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Repositories;

use App\Audit\Domain\AuditLogPage;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\ListAuditLogsCriteria;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Audit\Infrastructure\Persistence\Models\EloquentAuditLog;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;
use Illuminate\Database\Eloquent\Builder;

final class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    public function save(AuditLog $auditLog): void
    {
        $restaurantId = EloquentRestaurant::query()
            ->where('uuid', $auditLog->restaurantId()->value())
            ->value('id');

        $userId = null;
        if ($auditLog->userId() !== null) {
            $userId = EloquentUser::query()
                ->where('uuid', $auditLog->userId()->value())
                ->value('id');
        }

        EloquentAuditLog::query()->withoutGlobalScopes()->create([
            'uuid' => $auditLog->uuid()->value(),
            'restaurant_id' => $restaurantId,
            'entity_type' => $auditLog->entityType(),
            'entity_id' => $auditLog->entityId(),
            'action' => $auditLog->action()->value(),
            'category' => $auditLog->category()->value(),
            'severity' => $auditLog->severity()->value(),
            'summary' => $auditLog->summary(),
            'reason' => $auditLog->reason(),
            'session_id' => $auditLog->sessionId()?->value(),
            'anomaly_kind' => $auditLog->anomalyKind(),
            'integrity_hash' => $auditLog->integrityHash(),
            'prev_hash' => $auditLog->prevHash(),
            'metadata' => $auditLog->metadata(),
            'user_id' => $userId,
            'before' => $auditLog->before(),
            'after' => $auditLog->after(),
            'ip_address' => $auditLog->ipAddress(),
            'device_id' => $auditLog->deviceId(),
            'created_at' => $auditLog->createdAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function findByUuid(Uuid $restaurantId, Uuid $uuid, bool $includeArchived = false): ?AuditLog
    {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        if ($restaurantIdInt === null) {
            return null;
        }

        $query = EloquentAuditLog::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantIdInt)
            ->where('uuid', $uuid->value());

        if (! $includeArchived) {
            $query->whereNull('archived_at');
        }

        $model = $query->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function list(ListAuditLogsCriteria $criteria): AuditLogPage
    {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $criteria->restaurantId->value())
            ->value('id');

        if ($restaurantIdInt === null) {
            return AuditLogPage::empty();
        }

        $query = EloquentAuditLog::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantIdInt);

        // Live tail mode (since) — mutually exclusive with cursor.
        if ($criteria->sinceUuid !== null) {
            $sinceInternalId = EloquentAuditLog::query()
                ->withoutGlobalScopes()
                ->where('restaurant_id', $restaurantIdInt)
                ->where('uuid', $criteria->sinceUuid->value())
                ->value('id');

            if ($sinceInternalId === null) {
                return AuditLogPage::empty();
            }

            $this->applyFilters($query, $criteria, $restaurantIdInt);

            $models = $query->where('id', '>', $sinceInternalId)
                ->orderBy('id', 'asc')
                ->limit($criteria->limit)
                ->get();

            return new AuditLogPage(
                items: $models->map(fn (EloquentAuditLog $m): AuditLog => $this->toDomain($m))->all(),
                nextCursorCreatedAt: null,
                nextCursorInternalId: null,
                hasMore: false,
            );
        }

        // Standard descending paginated list.
        $this->applyFilters($query, $criteria, $restaurantIdInt);

        if ($criteria->cursorCreatedAt !== null && $criteria->cursorInternalId !== null) {
            $cursorCreatedAt = $criteria->cursorCreatedAt;
            $cursorInternalId = $criteria->cursorInternalId;
            $query->where(function (Builder $q) use ($cursorCreatedAt, $cursorInternalId): void {
                $q->where('created_at', '<', $cursorCreatedAt->format('Y-m-d H:i:s'))
                    ->orWhere(function (Builder $q2) use ($cursorCreatedAt, $cursorInternalId): void {
                        $q2->where('created_at', '=', $cursorCreatedAt->format('Y-m-d H:i:s'))
                            ->where('id', '<', $cursorInternalId);
                    });
            });
        }

        $limitPlusOne = $criteria->limit + 1;
        $models = $query
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limitPlusOne)
            ->get();

        $hasMore = $models->count() > $criteria->limit;
        $items = $hasMore ? $models->slice(0, $criteria->limit)->values() : $models;

        $lastItem = $items->last();
        $nextCreatedAt = null;
        $nextInternalId = null;
        if ($hasMore && $lastItem !== null) {
            $nextCreatedAt = $lastItem->created_at->toDateTimeImmutable();
            $nextInternalId = (int) $lastItem->id;
        }

        return new AuditLogPage(
            items: $items->map(fn (EloquentAuditLog $m): AuditLog => $this->toDomain($m))->all(),
            nextCursorCreatedAt: $nextCreatedAt,
            nextCursorInternalId: $nextInternalId,
            hasMore: $hasMore,
        );
    }

    public function lockAndGetLastHashForRestaurant(Uuid $restaurantId): ?string
    {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        if ($restaurantIdInt === null) {
            return null;
        }

        return EloquentAuditLog::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantIdInt)
            ->orderBy('id', 'desc')
            ->limit(1)
            ->lockForUpdate()
            ->value('integrity_hash');
    }

    public function countRecentByActionAndUser(
        Uuid $restaurantId,
        ActionSlug $slug,
        Uuid $userId,
        int $withinSeconds,
        bool $includeArchived = false,
    ): int {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        if ($restaurantIdInt === null) {
            return 0;
        }

        $userIdInt = EloquentUser::query()
            ->where('uuid', $userId->value())
            ->value('id');

        if ($userIdInt === null) {
            return 0;
        }

        $since = (new \DateTimeImmutable)->modify("-{$withinSeconds} seconds");

        $query = EloquentAuditLog::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantIdInt)
            ->where('action', $slug->value())
            ->where('user_id', $userIdInt)
            ->where('created_at', '>=', $since->format('Y-m-d H:i:s'));

        if (! $includeArchived) {
            $query->whereNull('archived_at');
        }

        return $query->count();
    }

    private function applyFilters(Builder $query, ListAuditLogsCriteria $criteria, int $restaurantIdInt): void
    {
        if (! $criteria->includeArchived) {
            $query->whereNull('archived_at');
        }

        if ($criteria->category !== null) {
            $query->where('category', $criteria->category);
        }
        if ($criteria->severity !== null) {
            $query->where('severity', $criteria->severity);
        }
        if ($criteria->userId !== null) {
            $userIdInt = EloquentUser::query()
                ->where('uuid', $criteria->userId->value())
                ->value('id');
            $query->where('user_id', $userIdInt ?? -1);
        }
        if ($criteria->deviceId !== null) {
            $query->where('device_id', $criteria->deviceId);
        }
        if ($criteria->dateFrom !== null) {
            $query->where('created_at', '>=', $criteria->dateFrom->format('Y-m-d H:i:s'));
        }
        if ($criteria->dateTo !== null) {
            $query->where('created_at', '<=', $criteria->dateTo->format('Y-m-d H:i:s'));
        }
        if ($criteria->anomalyOnly) {
            $query->whereNotNull('anomaly_kind');
        }
        if ($criteria->search !== null && strlen($criteria->search) >= 2) {
            $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $criteria->search).'%';
            $query->where(function (Builder $q) use ($term, $criteria): void {
                $q->where('summary', 'like', $term)
                    ->orWhere('action', 'like', $term)
                    ->orWhere('entity_id', '=', $criteria->search);
            });
        }
    }

    public function findAllByRestaurantOrdered(Uuid $restaurantId): array
    {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        if ($restaurantIdInt === null) {
            return [];
        }

        $models = EloquentAuditLog::query()
            ->withoutGlobalScopes()
            ->with(['restaurant', 'user'])
            ->where('restaurant_id', $restaurantIdInt)
            ->orderBy('id', 'asc')
            ->get();

        return $models->map(fn (EloquentAuditLog $m): AuditLog => $this->toDomain($m))->all();
    }

    private function toDomain(EloquentAuditLog $model): AuditLog
    {
        $restaurantUuid = $model->relationLoaded('restaurant') && $model->restaurant !== null
            ? $model->restaurant->uuid
            : EloquentRestaurant::query()
                ->where('id', $model->restaurant_id)
                ->value('uuid');

        $userUuid = null;
        if ($model->user_id !== null) {
            $userUuid = $model->relationLoaded('user') && $model->user !== null
                ? $model->user->uuid
                : EloquentUser::query()
                    ->where('id', $model->user_id)
                    ->value('uuid');
        }

        return AuditLog::fromPersistence(
            uuid: $model->uuid,
            restaurantId: $restaurantUuid,
            entityType: $model->entity_type,
            entityId: $model->entity_id,
            action: $model->action,
            category: $model->category,
            severity: $model->severity,
            summary: $model->summary,
            reason: $model->reason,
            sessionId: $model->session_id,
            anomalyKind: $model->anomaly_kind,
            integrityHash: $model->integrity_hash,
            prevHash: $model->prev_hash,
            metadata: is_array($model->metadata) ? $model->metadata : [],
            userId: $userUuid,
            before: is_array($model->before) ? $model->before : null,
            after: is_array($model->after) ? $model->after : null,
            ipAddress: $model->ip_address,
            deviceId: $model->device_id,
            createdAt: $model->created_at->toDateTimeImmutable(),
        );
    }
}
