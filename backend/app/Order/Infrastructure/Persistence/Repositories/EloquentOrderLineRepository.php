<?php

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentOrderLineRepository implements OrderLineRepositoryInterface
{
    public function save(OrderLine $orderLine): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $orderLine->getRestaurantId()->value())->value('id');
        $orderId = EloquentOrder::query()->where('uuid', $orderLine->getOrderId()->value())->value('id');
        $productId = EloquentProduct::query()->where('uuid', $orderLine->getProductId()->value())->value('id');
        $userId = EloquentUser::query()->where('uuid', $orderLine->getUserId()->value())->value('id');

        EloquentOrderLine::updateOrCreate(
            ['uuid' => $orderLine->getId()->value()],
            [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'product_id' => $productId,
                'user_id' => $userId,
                'quantity' => $orderLine->getQuantity(),
                'price' => $orderLine->getPrice(),
                'tax_percentage' => $orderLine->getTaxPercentage(),
            ],
        );
    }

    public function findById(Uuid $id): ?OrderLine
    {
        $model = EloquentOrderLine::where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?OrderLine
    {
        $model = EloquentOrderLine::where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOrderId(Uuid $orderId): array
    {
        $orderInternalId = EloquentOrder::query()->where('uuid', $orderId->value())->value('id');

        if ($orderInternalId === null) {
            return [];
        }

        $models = EloquentOrderLine::where('order_id', $orderInternalId)->get();

        return $models->map(fn ($model) => $this->toDomain($model))->toArray();
    }

    public function delete(Uuid $id): void
    {
        EloquentOrderLine::where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentOrderLine $model): OrderLine
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $orderUuid = EloquentOrder::query()->where('id', $model->order_id)->value('uuid');
        $productUuid = EloquentProduct::query()->where('id', $model->product_id)->value('uuid');
        $userUuid = EloquentUser::query()->where('id', $model->user_id)->value('uuid');

        return OrderLine::hydrate(
            id: Uuid::create($model->uuid),
            restaurantId: Uuid::create($restaurantUuid),
            uuid: Uuid::create($model->uuid),
            orderId: Uuid::create($orderUuid),
            productId: Uuid::create($productUuid),
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
