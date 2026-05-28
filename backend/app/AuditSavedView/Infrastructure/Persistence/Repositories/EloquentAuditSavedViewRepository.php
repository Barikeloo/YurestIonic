<?php

declare(strict_types=1);

namespace App\AuditSavedView\Infrastructure\Persistence\Repositories;

use App\AuditSavedView\Domain\Entity\AuditSavedView;
use App\AuditSavedView\Domain\Interfaces\AuditSavedViewRepositoryInterface;
use App\AuditSavedView\Infrastructure\Persistence\Models\EloquentAuditSavedView;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentAuditSavedViewRepository implements AuditSavedViewRepositoryInterface
{
    public function listByRestaurantAndUser(Uuid $restaurantId, Uuid $userId): array
    {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        $userIdInt = EloquentUser::query()
            ->where('uuid', $userId->value())
            ->value('id');

        if ($restaurantIdInt === null || $userIdInt === null) {
            return [];
        }

        $models = EloquentAuditSavedView::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantIdInt)
            ->where('user_id', $userIdInt)
            ->orderBy('name')
            ->get();

        return $models->map(fn (EloquentAuditSavedView $m): AuditSavedView => $this->toDomain($m))->all();
    }

    public function findByUuid(Uuid $restaurantId, Uuid $uuid): ?AuditSavedView
    {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        if ($restaurantIdInt === null) {
            return null;
        }

        $model = EloquentAuditSavedView::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantIdInt)
            ->where('uuid', $uuid->value())
            ->first();

        return $model !== null ? $this->toDomain($model) : null;
    }

    public function save(AuditSavedView $auditSavedView): void
    {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $auditSavedView->restaurantId()->value())
            ->value('id');

        $userIdInt = EloquentUser::query()
            ->where('uuid', $auditSavedView->userId()->value())
            ->value('id');

        EloquentAuditSavedView::query()->withoutGlobalScopes()->updateOrCreate(
            ['uuid' => $auditSavedView->uuid()->value()],
            [
                'restaurant_id' => $restaurantIdInt,
                'user_id' => $userIdInt,
                'name' => $auditSavedView->name(),
                'icon' => $auditSavedView->icon(),
                'filters' => $auditSavedView->filters(),
            ],
        );
    }

    public function delete(Uuid $restaurantId, Uuid $uuid): void
    {
        $restaurantIdInt = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        if ($restaurantIdInt === null) {
            return;
        }

        EloquentAuditSavedView::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantIdInt)
            ->where('uuid', $uuid->value())
            ->delete();
    }

    private function toDomain(EloquentAuditSavedView $model): AuditSavedView
    {
        $restaurantUuid = $model->relationLoaded('restaurant') && $model->restaurant !== null
            ? $model->restaurant->uuid
            : EloquentRestaurant::query()
                ->where('id', $model->restaurant_id)
                ->value('uuid');

        $userUuid = $model->relationLoaded('user') && $model->user !== null
            ? $model->user->uuid
            : EloquentUser::query()
                ->where('id', $model->user_id)
                ->value('uuid');

        return AuditSavedView::fromPersistence(
            uuid: $model->uuid,
            restaurantId: $restaurantUuid,
            userId: $userUuid,
            name: $model->name,
            icon: $model->icon,
            filters: is_array($model->filters) ? $model->filters : [],
            createdAt: $model->created_at->toDateTimeImmutable(),
            updatedAt: $model->updated_at->toDateTimeImmutable(),
        );
    }
}
