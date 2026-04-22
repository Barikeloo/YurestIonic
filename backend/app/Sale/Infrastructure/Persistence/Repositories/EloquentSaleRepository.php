<?php

namespace App\Sale\Infrastructure\Persistence\Repositories;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Sale\Infrastructure\Persistence\Models\EloquentSale;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
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
                'status' => $sale->status(),
                'cancelled_at' => $sale->cancelledAt()?->value(),
                'cancelled_by_user_id' => $cancelledByUserId,
                'cancel_reason' => $sale->cancellationReason(),
                'parent_sale_id' => $parentSaleId,
                'document_type' => $sale->documentType()->value(),
                'customer_fiscal_data' => $sale->customerFiscalData(),
            ],
        );
    }

    public function all(): array
    {
        return $this->model->newQuery()->get()->map(fn ($model) => $this->toDomain($model))->all();
    }

    public function getById(string $id): ?Sale
    {
        $model = $this->model->newQuery()->where('uuid', $id)->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findById(Uuid $id): ?Sale
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?Sale
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();

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

    public function findByCashSessionId(Uuid $cashSessionId): array
    {
        $cashSessionInternalId = EloquentCashSession::query()
            ->where('uuid', $cashSessionId->value())
            ->value('id');

        if ($cashSessionInternalId === null) {
            return [];
        }

        return $this->model->newQuery()
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
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $orderUuid = EloquentOrder::query()->where('id', $model->order_id)->value('uuid');
        $openedByUserUuid = EloquentUser::query()->where('id', $model->opened_by_user_id ?? $model->user_id)->value('uuid');
        $closedByUserUuid = $model->closed_by_user_id !== null
            ? EloquentUser::query()->where('id', $model->closed_by_user_id)->value('uuid')
            : null;
        $cancelledByUserUuid = $model->cancelled_by_user_id !== null
            ? EloquentUser::query()->where('id', $model->cancelled_by_user_id)->value('uuid')
            : null;
        $cashSessionUuid = $model->cash_session_id !== null
            ? EloquentCashSession::query()->where('id', $model->cash_session_id)->value('uuid')
            : null;
        $parentSaleUuid = $model->parent_sale_id !== null
            ? EloquentSale::query()->where('id', $model->parent_sale_id)->value('uuid')
            : null;

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
