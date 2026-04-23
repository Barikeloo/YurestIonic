<?php

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\ValueObject\OrderLinePrice;
use App\Order\Domain\ValueObject\OrderLineQuantity;
use App\Order\Domain\ValueObject\OrderLineTaxPercentage;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class EloquentOrderLineRepository implements OrderLineRepositoryInterface
{
    public function __construct(
        private EloquentOrderLine $model,
    ) {}

    public function save(OrderLine $orderLine): void
    {
        $restaurantId = EloquentRestaurant::query()->where('uuid', $orderLine->restaurantId()->value())->value('id');
        $orderId = EloquentOrder::query()->where('uuid', $orderLine->orderId()->value())->value('id');
        $productId = EloquentProduct::query()->where('uuid', $orderLine->productId()->value())->value('id');
        $userId = EloquentUser::query()->where('uuid', $orderLine->userId()->value())->value('id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $orderLine->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'product_id' => $productId,
                'user_id' => $userId,
                'quantity' => $orderLine->quantity()->value(),
                'price' => $orderLine->price()->value(),
                'tax_percentage' => $orderLine->taxPercentage()->value(),
                'diner_number' => $orderLine->dinerNumber(),
                'discount_percent' => $orderLine->discountPercent(),
                'discount_amount_cents' => $orderLine->discountAmountCents(),
                'discount_reason' => $orderLine->discountReason(),
                'is_invitation' => $orderLine->isInvitation(),
                'price_override_cents' => $orderLine->priceOverrideCents(),
                'notes' => $orderLine->notes(),
            ],
        );
    }

    public function findById(Uuid $id): ?OrderLine
    {
        $model = $this->model->newQuery()->where('uuid', $id->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByUuid(Uuid $uuid): ?OrderLine
    {
        $model = $this->model->newQuery()->where('uuid', $uuid->value())->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function findByOrderId(Uuid $orderId): array
    {
        $orderInternalId = EloquentOrder::query()->where('uuid', $orderId->value())->value('id');

        if ($orderInternalId === null) {
            return [];
        }

        $models = $this->model->newQuery()->where('order_id', $orderInternalId)->get();

        return $models->map(fn ($model) => $this->toDomain($model))->toArray();
    }

    public function findMatchingMergeableLine(
        Uuid $orderId,
        Uuid $productId,
        int $price,
        int $taxPercentage,
    ): ?OrderLine {
        $order = EloquentOrder::query()->where('uuid', $orderId->value())->first(['id', 'status']);

        if ($order === null || $order->status !== 'open') {
            return null;
        }

        $productInternalId = EloquentProduct::query()->where('uuid', $productId->value())->value('id');

        if ($productInternalId === null) {
            return null;
        }

        $model = $this->model->newQuery()
            ->where('order_id', $order->id)
            ->where('product_id', $productInternalId)
            ->where('price', $price)
            ->where('tax_percentage', $taxPercentage)
            ->first();

        return $model ? $this->toDomain($model) : null;
    }

    public function delete(Uuid $id): void
    {
        $this->model->newQuery()->where('uuid', $id->value())->delete();
    }

    private function toDomain(EloquentOrderLine $model): OrderLine
    {
        $restaurantUuid = EloquentRestaurant::query()->where('id', $model->restaurant_id)->value('uuid');
        $orderUuid = EloquentOrder::query()->where('id', $model->order_id)->value('uuid');
        $productUuid = EloquentProduct::query()->where('id', $model->product_id)->value('uuid');
        $userUuid = EloquentUser::query()->where('id', $model->user_id)->value('uuid');

        return OrderLine::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->uuid,
            $orderUuid,
            $productUuid,
            $userUuid,
            (int) $model->quantity,
            (int) $model->price,
            (int) $model->tax_percentage,
            $model->diner_number,
            $model->discount_percent,
            $model->discount_amount_cents,
            $model->discount_reason,
            (bool) $model->is_invitation,
            $model->price_override_cents,
            $model->notes,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
        );
    }
}
