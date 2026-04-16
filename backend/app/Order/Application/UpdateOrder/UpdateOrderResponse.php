<?php

namespace App\Order\Application\UpdateOrder;

use App\Order\Domain\Entity\Order;

final class UpdateOrderResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurant_id,
        public readonly string $status,
        public readonly string $table_id,
        public readonly string $opened_by_user_id,
        public readonly ?string $closed_by_user_id,
        public readonly int $diners,
        public readonly string $opened_at,
        public readonly ?string $closed_at,
    ) {}

    public static function create(Order $order): self
    {
        return new self(
            id: $order->id()->value(),
            uuid: $order->uuid()->value(),
            restaurant_id: $order->restaurantId()->value(),
            status: $order->status()->value(),
            table_id: $order->tableId()->value(),
            opened_by_user_id: $order->openedByUserId()->value(),
            closed_by_user_id: $order->closedByUserId()?->value(),
            diners: $order->diners()->value(),
            opened_at: $order->openedAt()?->format('Y-m-d H:i:s'),
            closed_at: $order->closedAt()?->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurant_id,
            'status' => $this->status,
            'table_id' => $this->table_id,
            'opened_by_user_id' => $this->opened_by_user_id,
            'closed_by_user_id' => $this->closed_by_user_id,
            'diners' => $this->diners,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
        ];
    }
}
