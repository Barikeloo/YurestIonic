<?php

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentSaleLineRepository implements SaleLineRepositoryInterface
{
    public function __construct(
        private EloquentSaleLine $model,
    ) {}

    public function save(SaleLine $saleLine): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $saleLine->restaurantId()->value())->value('id');
        $saleId = EloquentSale::query()->where('uuid', $saleLine->saleId()->value())->value('id');
        $orderLineId = EloquentOrderLine::query()->where('uuid', $saleLine->orderLineId()->value())->value('id');
        $productId = EloquentProduct::query()->where('uuid', $saleLine->productId()->value())->value('id');
        $userId = EloquentUser::query()->where('uuid', $saleLine->userId()->value())->value('id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $saleLine->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'sale_id' => $saleId,
                'order_line_id' => $orderLineId,
                'product_id' => $productId,
                'user_id' => $userId,
                'quantity' => $saleLine->quantity()->value(),
                'price' => $saleLine->price()->value(),
                'tax_percentage' => $saleLine->taxPercentage()->value(),
            ],
        );
    }

    public function findByUuid(Uuid $uuid): ?SaleLine
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySaleId(Uuid $saleId): array
    {
        $saleInternalId = EloquentSale::query()->where('uuid', $saleId->value())->value('id');

        if ($saleInternalId === null) {
            return [];
        }

        $models = $this->model->newQuery()->where('sale_id', $saleInternalId)->get();

        return $models->map(fn ($model) => $this->toDomain($model))->toArray();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentSaleLine $model): SaleLine
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $saleUuid = EloquentSale::query()->where('id', $model->sale_id)->value('uuid');
        $orderLineUuid = EloquentOrderLine::query()->where('id', $model->order_line_id)->value('uuid');
        $productUuid = EloquentProduct::query()->where('id', $model->product_id)->value('uuid');
        $userUuid = EloquentUser::query()->where('id', $model->user_id)->value('uuid');

        return SaleLine::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->uuid,
            $saleUuid,
            $orderLineUuid,
            $productUuid,
            $userUuid,
            (int) $model->quantity,
            (int) $model->price,
            (int) $model->tax_percentage,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
