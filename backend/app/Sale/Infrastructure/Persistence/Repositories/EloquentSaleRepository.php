<?php

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentSaleRepository implements SaleRepositoryInterface
{
    public function __construct(
        private EloquentSale $model,
    ) {}

    public function save(Sale $sale): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $sale->restaurantId()->value())->value('id');
        $orderId = EloquentOrder::query()->where('uuid', $sale->orderId()->value())->value('id');
        $openedByUserId = EloquentUser::query()->where('uuid', $sale->openedByUserId()->value())->value('id');
        $closedByUserId = $sale->closedByUserId() !== null
            ? EloquentUser::query()->where('uuid', $sale->closedByUserId()->value())->value('id')
            : null;
        $cancelledByUserId = $sale->cancelledByUserId() !== null
            ? EloquentUser::query()->where('uuid', $sale->cancelledByUserId()->value())->value('id')
            : null;
        $cashSessionId = $sale->cashSessionId() !== null
            ? EloquentCashSession::query()->where('uuid', $sale->cashSessionId()->value())->value('id')
            : null;
        $parentSaleId = $sale->parentSaleId() !== null
            ? EloquentSale::query()->where('uuid', $sale->parentSaleId()->value())->value('id')
            : null;

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $sale->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'user_id' => $openedByUserId,
                'opened_by_user_id' => $openedByUserId,
                'closed_by_user_id' => $closedByUserId,
                'ticket_number' => $sale->ticketNumber()?->value(),
                'value_date' => $sale->valueDate()->value(),
                'total' => $sale->total()->value(),
                'cash_session_id' => $cashSessionId,
                'status' => $sale->status()->value(),
                'cancelled_at' => $sale->cancelledAt()?->value(),
                'cancelled_by_user_id' => $cancelledByUserId,
                'cancel_reason' => $sale->cancellationReason(),
                'parent_sale_id' => $parentSaleId,
                'document_type' => $sale->documentType()->value(),
                'customer_fiscal_data' => $sale->customerFiscalData()?->toArray(),
            ],
        );
    }

    public function all(): array
    {
        return $this->model->newQuery()
            ->with(['restaurant', 'order', 'openedByUser', 'closedByUser', 'cancelledByUser', 'cashSession', 'parentSale'])
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->all();
    }

    public function findByUuid(Uuid $uuid): ?Sale
    {
        $model = $this->model->newQuery()
            ->with(['restaurant', 'order', 'openedByUser', 'closedByUser', 'cancelledByUser', 'cashSession', 'parentSale'])
            ->where('uuid', $uuid->value())
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOrderId(Uuid $orderId): ?Sale
    {
        $orderInternalId = EloquentOrder::query()->where('uuid', $orderId->value())->value('id');

        if ($orderInternalId === null) {
            return null;
        }

        $model = $this->model->newQuery()->where('order_id', $orderInternalId)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findAllByOrderId(Uuid $orderId): array
    {
        $orderInternalId = EloquentOrder::query()->where('uuid', $orderId->value())->value('id');

        if ($orderInternalId === null) {
            return [];
        }

        $models = $this->model->newQuery()
            ->with(['restaurant', 'order', 'openedByUser', 'closedByUser', 'cancelledByUser', 'cashSession', 'parentSale'])
            ->where('order_id', $orderInternalId)
            ->get();

        return $models->map(fn ($model) => $this->toDomain($model))->toArray();
    }

    public function findByCashSessionId(Uuid $cashSessionId): array
    {
        $cashSessionInternalId = EloquentCashSession::query()
            ->where('uuid', $cashSessionId->value())
            ->value('id');

        if ($cashSessionInternalId === null) {
            return [];
        }

        return $this->model->newQuery()
            ->with(['restaurant', 'order', 'openedByUser', 'closedByUser', 'cancelledByUser', 'cashSession', 'parentSale'])
            ->where('cash_session_id', $cashSessionInternalId)
            ->get()
            ->map(fn ($model) => $this->toDomain($model))
            ->all();
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('uuid', $id->value())->delete();
    }

    public function nextTicketNumber(Uuid $restaurantId): int
    {
        $restaurantInternalId = EloquentRestaurant::query()
            ->where('uuid', $restaurantId->value())
            ->value('id');

        $max = $this->model->newQuery()
            ->where('restaurant_id', $restaurantInternalId)
            ->max('ticket_number');

        return $max !== null ? (int) $max + 1 : 1;
    }

    private function toDomain(EloquentSale $model): Sale
    {
        // Use eager-loaded relationships to avoid N+1 queries
        $restaurantUuid = $model->restaurant?->uuid ?? '';
        $orderUuid = $model->order?->uuid ?? '';
        $openedByUserUuid = $model->openedByUser?->uuid ?? $model->user?->uuid ?? '';
        $closedByUserUuid = $model->closedByUser?->uuid ?? null;
        $cancelledByUserUuid = $model->cancelledByUser?->uuid ?? null;
        $cashSessionUuid = $model->cashSession?->uuid ?? null;
        $parentSaleUuid = $model->parentSale?->uuid ?? null;

        return Sale::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->uuid,
            $orderUuid,
            $openedByUserUuid,
            $closedByUserUuid,
            $model->ticket_number !== null ? (int) $model->ticket_number : null,
            $model->value_date->toDateTimeImmutable(),
            (int) $model->total,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
            $cashSessionUuid,
            $cancelledByUserUuid,
            $model->cancel_reason,
            $model->cancelled_at?->toDateTimeImmutable(),
            $model->status ?? 'closed',
            $parentSaleUuid,
            $model->document_type ?? 'simplified',
            $model->customer_fiscal_data,
        );
    }
}
