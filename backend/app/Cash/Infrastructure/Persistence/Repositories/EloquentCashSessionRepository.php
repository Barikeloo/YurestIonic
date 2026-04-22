<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Repositories;

use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentCashSessionRepository implements CashSessionRepositoryInterface
{
    public function __construct(
        private EloquentCashSession $model,
    ) {}

    public function save(CashSession $cashSession): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $cashSession->restaurantId()->value())->value('id');
        $openedByUserId = EloquentUser::query()->where('uuid', $cashSession->openedByUserId()->value())->value('id');
        $closedByUserId = $cashSession->closedByUserId() !== null
            ? EloquentUser::query()->where('uuid', $cashSession->closedByUserId()->value())->value('id')
            : null;

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $cashSession->uuid()->value()],
            [
                'restaurant_id' => $restaurantId,
                'device_id' => $cashSession->deviceId(),
                'opened_by_user_id' => $openedByUserId,
                'closed_by_user_id' => $closedByUserId,
                'opened_at' => $cashSession->openedAt()?->value(),
                'closed_at' => $cashSession->closedAt()?->value(),
                'initial_amount_cents' => $cashSession->initialAmount()->toCents(),
                'final_amount_cents' => $cashSession->finalAmount()?->toCents(),
                'expected_amount_cents' => $cashSession->expectedAmount()?->toCents(),
                'discrepancy_cents' => $cashSession->discrepancy()?->toCents(),
                'discrepancy_reason' => $cashSession->discrepancyReason(),
                'z_report_number' => $cashSession->zReportNumber(),
                'z_report_hash' => $cashSession->zReportHash(),
                'notes' => $cashSession->notes(),
                'status' => $cashSession->status()->value(),
            ],
        );
    }

    public function getById(string $id): ?CashSession
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?CashSession
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?CashSession
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findActiveByDeviceId(string $deviceId, Uuid $restaurantId): ?CashSession
    {
        $restaurantIdInt = EloquentRestaurant::query()->where('uuid', $restaurantId->value())->value('id');
        $model = $this->model->newQuery()
            ->where('restaurant_id', $restaurantIdInt)
            ->where('device_id', $deviceId)
            ->where('status', 'open')
            ->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByRestaurantId(Uuid $restaurantId): array
    {
        $restaurantIdInt = EloquentRestaurant::query()->where('uuid', $restaurantId->value())->value('id');
        return $this->model->newQuery()
            ->where('restaurant_id', $restaurantIdInt)
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentCashSession $model): CashSession
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $openedByUserUuid = EloquentUser::query()->where('id', $model->opened_by_user_id)->value('uuid');
        $closedByUserUuid = $model->closed_by_user_id
            ? EloquentUser::query()->where('id', $model->closed_by_user_id)->value('uuid')
            : null;

        return CashSession::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->device_id,
            $openedByUserUuid,
            $closedByUserUuid,
            $model->opened_at?->toDateTimeImmutable(),
            $model->closed_at?->toDateTimeImmutable(),
            $model->initial_amount_cents,
            $model->final_amount_cents,
            $model->expected_amount_cents,
            $model->discrepancy_cents,
            $model->discrepancy_reason,
            $model->z_report_number,
            $model->z_report_hash,
            $model->notes,
            $model->status,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
