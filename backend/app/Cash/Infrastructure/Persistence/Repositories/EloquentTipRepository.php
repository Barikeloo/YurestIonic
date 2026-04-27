<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Repositories;

use App\Cash\Domain\Entity\Tip;
use App\Cash\Domain\Interfaces\TipRepositoryInterface;
use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Cash\Infrastructure\Persistence\Models\EloquentTip;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentTipRepository implements TipRepositoryInterface
{
    public function __construct(
        private EloquentTip $model,
    ) {}

    public function save(Tip $tip): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $tip->restaurantId()->value())->value('id');
        $saleId = EloquentSale::query()->where('uuid', $tip->saleId()->value())->value('id');
        $cashSessionId = EloquentCashSession::query()->where('uuid', $tip->cashSessionId()->value())->value('id');
        $beneficiaryUserId = $tip->beneficiaryUserId() !== null
            ? EloquentUser::query()->where('uuid', $tip->beneficiaryUserId()->value())->value('id')
            : null;

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $tip->uuid()->value()],
            [
                'restaurant_id' => $restaurantId,
                'sale_id' => $saleId,
                'cash_session_id' => $cashSessionId,
                'amount_cents' => $tip->amount()->toCents(),
                'source' => $tip->source()->value(),
                'beneficiary_user_id' => $beneficiaryUserId,
            ],
        );
    }

    public function getById(string $id): ?Tip
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?Tip
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Tip
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findBySaleId(Uuid $saleId): array
    {
        $saleIdInt = EloquentSale::query()->where('uuid', $saleId->value())->value('id');
        return $this->model->newQuery()
            ->where('sale_id', $saleIdInt)
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->all();
    }

    public function findByCashSessionId(Uuid $cashSessionId): array
    {
        $cashSessionIdInt = EloquentCashSession::query()->where('uuid', $cashSessionId->value())->value('id');
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

    private function toDomain(EloquentTip $model): Tip
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $saleUuid = EloquentSale::query()->where('id', $model->sale_id)->value('uuid');
        $cashSessionUuid = EloquentCashSession::query()->where('id', $model->cash_session_id)->value('uuid');
        $beneficiaryUserUuid = $model->beneficiary_user_id
            ? EloquentUser::query()->where('id', $model->beneficiary_user_id)->value('uuid')
            : null;

        return Tip::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->uuid,
            $saleUuid,
            $cashSessionUuid,
            (int) $model->amount_cents,
            $model->source,
            $beneficiaryUserUuid,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
