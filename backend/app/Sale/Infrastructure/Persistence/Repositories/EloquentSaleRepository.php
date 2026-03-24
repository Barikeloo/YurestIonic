<?php

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function save(Sale $sale): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $sale->getRestaurantId()->value())->value('id');
        $orderId = EloquentOrder::query()->where('uuid', $sale->getOrderId()->value())->value('id');
        $userId = EloquentUser::query()->where('uuid', $sale->getUserId()->value())->value('id');

        EloquentSale::updateOrCreate(
            ['uuid' => $sale->getId()->value()],
            [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'ticket_number' => $sale->getTicketNumber(),
                'value_date' => $sale->getValueDate()->value(),
                'total' => $sale->getTotal(),
            ],
        );
    }

    public function all(): array
    {
        return EloquentSale::query()->get()->map(fn ($model) => $this->toDomain($model))->all();
    }

    public function getById(string $id): ?Sale
    {
        $model = EloquentSale::where('uuid', $id)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?Sale
    {
        $model = EloquentSale::where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Sale
    {
        $model = EloquentSale::where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOrderId(Uuid $orderId): ?Sale
    {
        $orderInternalId = EloquentOrder::query()->where('uuid', $orderId->value())->value('id');

        if ($orderInternalId === null) {
            return null;
        }

        $model = EloquentSale::where('order_id', $orderInternalId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function delete(Uuid $id): void
    {
        EloquentSale::where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentSale $model): Sale
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $orderUuid = EloquentOrder::query()->where('id', $model->order_id)->value('uuid');
        $userUuid = EloquentUser::query()->where('id', $model->user_id)->value('uuid');

        return Sale::hydrate(
            id: Uuid::create($model->uuid),
            restaurantId: Uuid::create($restaurantUuid),
            uuid: Uuid::create($model->uuid),
            orderId: Uuid::create($orderUuid),
            userId: Uuid::create($userUuid),
            ticketNumber: $model->ticket_number,
            valueDate: DomainDateTime::create($model->value_date->toDateTimeImmutable()),
            total: $model->total,
            createdAt: DomainDateTime::create($model->created_at->toDateTimeImmutable()),
            updatedAt: DomainDateTime::create($model->updated_at->toDateTimeImmutable()),
            deletedAt: $model->deleted_at ? DomainDateTime::create($model->deleted_at->toDateTimeImmutable()) : null,
        );
    }
}
