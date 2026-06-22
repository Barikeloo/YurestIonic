<?php

namespace App\Order\Infrastructure\Persistence\Repositories;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Infrastructure\Persistence\Models\EloquentOrder;
use App\Order\Infrastructure\Persistence\Models\EloquentOrderLine;
use App\Product\Infrastructure\Persistence\Models\EloquentProduct;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
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
        $productId = $orderLine->productId() !== null
            ? EloquentProduct::query()->where('uuid', $orderLine->productId()->value())->value('id')
            : null;
        $userId = EloquentUser::query()->where('uuid', $orderLine->userId()->value())->value('id');

        $this->model->newQuery()->updateOrCreate(
            ['uuid' => $orderLine->id()->value()],
            [
                'restaurant_id' => $restaurantId,
                'order_id' => $orderId,
                'product_id' => $productId,
                'variant_id' => $orderLine->variantId()?->value(),
                'variant_name' => $orderLine->variantName(),
                'modifiers' => $orderLine->modifiers(),
                'menu_id' => $orderLine->menuId()?->value(),
                'menu_name' => $orderLine->menuName(),
                'menu_selections' => $orderLine->menuSelections(),
                'user_id' => $userId,
                'quantity' => $orderLine->quantity()->value(),
                'price' => $orderLine->price()->value(),
                'tax_percentage' => $orderLine->taxPercentage()->value(),
                'diner_number' => $orderLine->dinerNumber()?->value(),
                'discount_percent' => $orderLine->discountPercent()?->value(),
                'discount_amount_cents' => $orderLine->discountAmount()?->value(),
                'discount_reason' => $orderLine->discountReason(),
                'is_invitation' => $orderLine->isInvitation(),
                'price_override_cents' => $orderLine->priceOverride()?->value(),
                'notes' => $orderLine->notes(),
            ],
        );
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
        $productUuid = $model->product_id !== null
            ? EloquentProduct::query()->where('id', $model->product_id)->value('uuid')
            : null;
        $userUuid = $model->user_id !== null
            ? EloquentUser::query()->where('id', $model->user_id)->value('uuid')
            : null;

        return OrderLine::fromPersistence(
            $model->uuid,
            $restaurantUuid,
            $model->uuid,
            $orderUuid,
            $productUuid,
            $model->variant_id,
            $model->variant_name,
            $model->modifiers,
            $model->menu_id,
            $model->menu_name,
            $model->menu_selections,
            $userUuid,
            (int) $model->quantity,
            (int) $model->price,
            (int) $model->tax_percentage,
            $model->diner_number !== null ? (int) $model->diner_number : null,
            $model->discount_percent !== null ? (int) $model->discount_percent : null,
            $model->discount_amount_cents !== null ? (int) $model->discount_amount_cents : null,
            $model->discount_reason,
            (bool) $model->is_invitation,
            $model->price_override_cents !== null ? (int) $model->price_override_cents : null,
            $model->notes,
            $model->created_at->toDateTimeImmutable(),
            $model->updated_at->toDateTimeImmutable(),
            $model->deleted_at?->toDateTimeImmutable(),
            $model->origin ?? 'tpv',
            $model->guest_name ?? null,
            $model->send_status ?? null,
            $model->guest_round_id ?? null,
        );
    }
}
