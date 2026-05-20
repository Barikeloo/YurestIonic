<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\OrderTransfer;
use App\Order\Domain\Interfaces\OrderTransferRepositoryInterface;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderTransfer;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Infrastructure\Persistence\Models\EloquentTable;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentOrderTransferRepository implements OrderTransferRepositoryInterface
{
    public function __construct(
        private EloquentOrderTransfer $model,
    ) {}

    public function save(OrderTransfer $transfer): void
    {
        $orderId = EloquentOrder::query()->where('uuid', $transfer->orderId()->value())->value('id');
        $fromTableId = EloquentTable::query()->where('uuid', $transfer->fromTableId()->value())->value('id');
        $toTableId = EloquentTable::query()->where('uuid', $transfer->toTableId()->value())->value('id');
        $userId = EloquentUser::query()->where('uuid', $transfer->transferredByUserId()->value())->value('id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $transfer->id()->value()],
            [
                'order_id' => $orderId,
                'from_table_id' => $fromTableId,
                'to_table_id' => $toTableId,
                'transferred_by_user_id' => $userId,
                'transferred_at' => $transfer->transferredAt()->value(),
            ],
        );
    }

    public function findByOrderId(Uuid $orderId): array
    {
        $orderInternalId = EloquentOrder::query()->where('uuid', $orderId->value())->value('id');

        if ($orderInternalId === null) {
            return [];
        }

        return $this->model->newQuery()
            ->with(['order', 'fromTable', 'toTable', 'transferredByUser'])
            ->where('order_id', $orderInternalId)
            ->orderBy('transferred_at', 'desc')
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->all();
    }

    private function toDomain(EloquentOrderTransfer $model): OrderTransfer
    {
        $orderUuid = $model->order?->uuid ?? '';
        $fromTableUuid = $model->fromTable?->uuid ?? '';
        $toTableUuid = $model->toTable?->uuid ?? '';
        $userUuid = $model->transferredByUser?->uuid ?? '';

        return OrderTransfer::fromPersistence(
            $model->uuid,
            $orderUuid,
            $fromTableUuid,
            $toTableUuid,
            $userUuid,
            $model->transferred_at->toDateTimeImmutable(),
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
        );
    }
}
