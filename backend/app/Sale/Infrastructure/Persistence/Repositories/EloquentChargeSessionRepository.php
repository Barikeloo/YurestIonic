<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\ChargeSessionModel;
use App\Shared\Domain\ValueObject\Uuid;

final class EloquentChargeSessionRepository implements ChargeSessionRepositoryInterface
{
    public function save(ChargeSession $chargeSession): void
    {
        ChargeSessionModel::updateOrCreate(
            ['id' => $chargeSession->id()->value()],
            [
                'restaurant_id' => $chargeSession->restaurantId()->value(),
                'order_id' => $chargeSession->orderId()->value(),
                'opened_by_user_id' => $chargeSession->openedByUserId()->value(),
                'diners_count' => $chargeSession->dinersCount(),
                'total_cents' => $chargeSession->totalCents(),
                'status' => $chargeSession->status()->value(),
                'cancelled_by_user_id' => $chargeSession->cancelledByUserId()?->value(),
                'cancellation_reason' => $chargeSession->cancellationReason(),
                'cancelled_at' => $chargeSession->cancelledAt()?->value(),
            ]
        );
    }

    public function findById(Uuid $id): ?ChargeSession
    {
        $model = ChargeSessionModel::find($id->value());

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findActiveByOrderId(Uuid $orderId): ?ChargeSession
    {
        $model = ChargeSessionModel::query()
            ->where('order_id', $orderId->value())
            ->where('status', 'active')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findCurrentByOrderId(Uuid $orderId): ?ChargeSession
    {
        $model = ChargeSessionModel::query()
            ->where('order_id', $orderId->value())
            ->orderByDesc('created_at')
            ->first();

        if ($model === null) {
            return null;
        }

        return $this->toEntity($model);
    }

    public function findByOrderId(Uuid $orderId): array
    {
        $models = ChargeSessionModel::query()
            ->where('order_id', $orderId->value())
            ->orderBy('created_at', 'desc')
            ->get();

        return $models->map(fn ($model) => $this->toEntity($model))->toArray();
    }

    public function delete(Uuid $id): void
    {
        ChargeSessionModel::destroy($id->value());
    }

    private function toEntity(ChargeSessionModel $model): ChargeSession
    {
        return ChargeSession::fromPersistence(
            $model->id,
            $model->restaurant_id,
            $model->order_id,
            $model->opened_by_user_id,
            $model->diners_count,
            $model->total_cents,
            $model->status,
            $this->toImmutable($model->created_at),
            $this->toImmutable($model->updated_at),
            $this->toImmutable($model->deleted_at),
            $model->cancelled_by_user_id,
            $model->cancellation_reason,
            $this->toImmutable($model->cancelled_at),
        );
    }

    private function toImmutable(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return \DateTimeImmutable::createFromInterface($value);
        }
        return new \DateTimeImmutable((string) $value);
    }
}
