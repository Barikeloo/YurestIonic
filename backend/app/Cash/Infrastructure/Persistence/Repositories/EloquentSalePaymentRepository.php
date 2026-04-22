<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Persistence\Repositories;

use App\Cash\Domain\Entity\SalePayment;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Cash\Infrastructure\Persistence\Models\EloquentSalePayment;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentSalePaymentRepository implements SalePaymentRepositoryInterface
{
    public function __construct(
        private EloquentSalePayment $model,
    ) {}

    public function save(SalePayment $salePayment): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $salePayment->restaurantId()->value())->value('id');
        $saleId = EloquentSale::query()->where('uuid', $salePayment->saleId()->value())->value('id');
        $cashSessionId = EloquentCashSession::query()->where('uuid', $salePayment->cashSessionId()->value())->value('id');
        $userId = EloquentUser::query()->where('uuid', $salePayment->userId()->value())->value('id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $salePayment->uuid()->value()],
            [
                'restaurant_id' => $restaurantId,
                'sale_id' => $saleId,
                'cash_session_id' => $cashSessionId,
                'method' => $salePayment->method()->value(),
                'amount_cents' => $salePayment->amount()->toCents(),
                'metadata' => $salePayment->metadata() !== null ? json_encode($salePayment->metadata()) : null,
                'user_id' => $userId,
            ],
        );
    }

    public function getById(string $id): ?SalePayment
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?SalePayment
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();
        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?SalePayment
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

    private function toDomain(EloquentSalePayment $model): SalePayment
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $saleUuid = EloquentSale::query()->where('id', $model->sale_id)->value('uuid');
        $cashSessionUuid = EloquentCashSession::query()->where('id', $model->cash_session_id)->value('uuid');
        $userUuid = EloquentUser::query()->where('id', $model->user_id)->value('uuid');

        return SalePayment::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->uuid,
            $saleUuid,
            $cashSessionUuid,
            $model->method,
            $model->amount_cents,
            $model->metadata,
            $userUuid,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
