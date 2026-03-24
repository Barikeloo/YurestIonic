<?php

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentSaleLine;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentSaleLineRepository implements SaleLineRepositoryInterface
{
    public function save(SaleLine $saleLine): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $saleLine->getRestaurantId()->value())->value('id');
        $saleId = EloquentSale::query()->where('uuid', $saleLine->getSaleId()->value())->value('id');
        $orderLineId = EloquentOrderLine::query()->where('uuid', $saleLine->getOrderLineId()->value())->value('id');
        $userId = EloquentUser::query()->where('uuid', $saleLine->getUserId()->value())->value('id');

        EloquentSaleLine::updateOrCreate(
            ['uuid' => $saleLine->getId()->value()],
            [
                'restaurant_id' => $restaurantId,
                'sale_id' => $saleId,
                'order_line_id' => $orderLineId,
                'user_id' => $userId,
                'quantity' => $saleLine->getQuantity(),
                'price' => $saleLine->getPrice(),
                'tax_percentage' => $saleLine->getTaxPercentage(),
            ],
        );
    }

    public function findById(Uuid $id): ?SaleLine
    {
        $model = EloquentSaleLine::where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?SaleLine
    {
        $model = EloquentSaleLine::where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findBySaleId(Uuid $saleId): array
    {
        $saleInternalId = EloquentSale::query()->where('uuid', $saleId->value())->value('id');

        if ($saleInternalId === null) {
            return [];
        }

        $models = EloquentSaleLine::where('sale_id', $saleInternalId)->get();

        return $models->map(fn ($model) => $this->toDomain($model))->toArray();
    }

    public function delete(Uuid $id): void
    {
        EloquentSaleLine::where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentSaleLine $model): SaleLine
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $saleUuid = EloquentSale::query()->where('id', $model->sale_id)->value('uuid');
        $orderLineUuid = EloquentOrderLine::query()->where('id', $model->order_line_id)->value('uuid');
        $userUuid = EloquentUser::query()->where('id', $model->user_id)->value('uuid');

        return SaleLine::hydrate(
            id: Uuid::create($model->uuid),
            restaurantId: Uuid::create($restaurantUuid),
            uuid: Uuid::create($model->uuid),
            saleId: Uuid::create($saleUuid),
            orderLineId: Uuid::create($orderLineUuid),
            userId: Uuid::create($userUuid),
            quantity: $model->quantity,
            price: $model->price,
            taxPercentage: $model->tax_percentage,
            createdAt: DomainDateTime::create($model->created_at->toDateTimeImmutable()),
            updatedAt: DomainDateTime::create($model->updated_at->toDateTimeImmutable()),
            deletedAt: $model->deleted_at ? DomainDateTime::create($model->deleted_at->toDateTimeImmutable()) : null,
        );
    }
}
