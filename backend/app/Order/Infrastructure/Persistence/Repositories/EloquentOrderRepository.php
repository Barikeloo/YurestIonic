<?php

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $order->getRestaurantId()->value())->value('id');
        $tableId = EloquentTable::query()->where('uuid', $order->getTableId()->value())->value('id');
        $openedByUserId = EloquentUser::query()->where('uuid', $order->getOpenedByUserId()->value())->value('id');
        $closedByUserId = $order->getClosedByUserId() !== null
            ? EloquentUser::query()->where('uuid', $order->getClosedByUserId()->value())->value('id')
            : null;

        EloquentOrder::updateOrCreate(
            ['uuid' => $order->getId()->value()],
            [
                'restaurant_id' => $restaurantId,
                'status' => $order->getStatus()->value(),
                'table_id' => $tableId,
                'opened_by_user_id' => $openedByUserId,
                'closed_by_user_id' => $closedByUserId,
                'diners' => $order->getDiners(),
                'opened_at' => $order->getOpenedAt()->value(),
                'closed_at' => $order->getClosedAt()?->value(),
            ],
        );
    }

    public function all(): array
    {
        return EloquentOrder::query()->get()->map(fn ($model) => $this->toDomain($model))->all();
    }

    public function getById(string $id): ?Order
    {
        $model = EloquentOrder::where('uuid', $id)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?Order
    {
        $model = EloquentOrder::where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Order
    {
        $model = EloquentOrder::where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByTableId(Uuid $tableId): ?Order
    {
        $model = EloquentOrder::where('table_id', $tableId->value())
            ->where('status', 'open')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function delete(Uuid $id): void
    {
        EloquentOrder::where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentOrder $model): Order
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $tableUuid = EloquentTable::query()->where('id', $model->table_id)->value('uuid');
        $openedByUserUuid = EloquentUser::query()->where('id', $model->opened_by_user_id)->value('uuid');
        $closedByUserUuid = $model->closed_by_user_id
            ? EloquentUser::query()->where('id', $model->closed_by_user_id)->value('uuid')
            : null;

        return Order::hydrate(
            id: Uuid::create($model->uuid),
            restaurantId: Uuid::create($restaurantUuid),
            uuid: Uuid::create($model->uuid),
            status: OrderStatus::create($model->status),
            tableId: Uuid::create($tableUuid),
            openedByUserId: Uuid::create($openedByUserUuid),
            closedByUserId: $closedByUserUuid ? Uuid::create($closedByUserUuid) : null,
            diners: $model->diners,
            openedAt: DomainDateTime::create($model->opened_at->toDateTimeImmutable()),
            closedAt: $model->closed_at ? DomainDateTime::create($model->closed_at->toDateTimeImmutable()) : null,
            createdAt: DomainDateTime::create($model->created_at->toDateTimeImmutable()),
            updatedAt: DomainDateTime::create($model->updated_at->toDateTimeImmutable()),
            deletedAt: $model->deleted_at ? DomainDateTime::create($model->deleted_at->toDateTimeImmutable()) : null,
        );
    }
}
