<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Repositories;

use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Infrastructure\Persistence\Models\EloquentCashMovement;
use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentCashMovementRepository implements CashMovementRepositoryInterface
{
    public function __construct(
        private EloquentCashMovement $model,
    ) {}

    public function save(CashMovement $cashMovement): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $cashMovement->restaurantId()->value())->value('id');
        $cashSessionId = EloquentCashSession::query()
            ->where('uuid', $cashMovement->cashSessionId()->value())
            ->value('id');
        $userId = EloquentUser::query()->where('uuid', $cashMovement->userId()->value())->value('id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $cashMovement->uuid()->value()],
            [
                'restaurant_id' => $restaurantId,
                'cash_session_id' => $cashSessionId,
                'type' => $cashMovement->type()->value(),
                'reason_code' => $cashMovement->reasonCode()->value(),
                'amount_cents' => $cashMovement->amount()->toCents(),
                'description' => $cashMovement->description(),
                'user_id' => $userId,
            ],
        );
    }

    public function getById(string $id): ?CashMovement
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?CashMovement
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?CashMovement
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByCashSessionId(Uuid $cashSessionId): array
    {
        $cashSessionIdInt = EloquentCashSession::query()
            ->where('uuid', $cashSessionId->value())
            ->value('id');

        return $this->model->newQuery()
            ->where('cash_session_id', $cashSessionIdInt)
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentCashMovement $model): CashMovement
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $cashSessionUuid = EloquentCashSession::query()
            ->where('id', $model->cash_session_id)
            ->value('uuid');
        $userUuid = EloquentUser::query()->where('id', $model->user_id)->value('uuid');

        return CashMovement::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->uuid,
            $cashSessionUuid,
            $model->type,
            $model->reason_code,
            (int) $model->amount_cents,
            $model->description,
            $userUuid,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
