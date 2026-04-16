<?php

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        private EloquentOrder $model,
    ) {}

    public function save(Order $order): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $order->restaurantId()->value())->value('id');
        $tableId = EloquentTable::query()->where('uuid', $order->tableId()->value())->value('id');
        $openedByUserId = EloquentUser::query()->where('uuid', $order->openedByUserId()->value())->value('id');
        $closedByUserId = $order->closedByUserId() !== null
            ? EloquentUser::query()->where('uuid', $order->closedByUserId()->value())->value('id')
            : null;

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $order->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'status' => $order->status()->value(),
                'table_id' => $tableId,
                'opened_by_user_id' => $openedByUserId,
                'closed_by_user_id' => $closedByUserId,
                'diners' => $order->diners()->value(),
                'opened_at' => $order->openedAt()?->value(),
                'closed_at' => $order->closedAt()?->value(),
            ],
        );
    }

    public function all(): array
    {
        return $this->model->newQuery()->get()->map(fn ($model) => $this->toDomain($model))->all();
    }

    public function getById(string $id): ?Order
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?Order
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Order
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByTableId(Uuid $tableId): ?Order
    {
        $model = $this->model->newQuery()->where('table_id', $tableId->value())
            ->where('status', 'open')
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentOrder $model): Order
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $tableUuid = EloquentTable::query()->where('id', $model->table_id)->value('uuid');
        $openedByUserUuid = EloquentUser::query()->where('id', $model->opened_by_user_id)->value('uuid');
        $closedByUserUuid = $model->closed_by_user_id
            ? EloquentUser::query()->where('id', $model->closed_by_user_id)->value('uuid')
            : null;

        return Order::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->uuid,
            $model->status,
            $tableUuid,
            $openedByUserUuid,
            $closedByUserUuid,
            (int) $model->diners,
            $model->opened_at?->toDateTimeImmutable(),
            $model->closed_at?->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
