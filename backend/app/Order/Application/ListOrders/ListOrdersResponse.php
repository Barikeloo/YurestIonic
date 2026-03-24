<?php

namespace App\Order\Application\ListOrders;

use App\Order\Domain\Entity\Order;

final class ListOrdersResponse
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
            id: $order->getId()->value(),
            uuid: $order->getUuid()->value(),
            restaurant_id: $order->getRestaurantId()->value(),
            status: $order->getStatus()->value(),
            table_id: $order->getTableId()->value(),
            opened_by_user_id: $order->getOpenedByUserId()->value(),
            closed_by_user_id: $order->getClosedByUserId()?->value(),
            diners: $order->getDiners(),
            opened_at: $order->getOpenedAt()->format('Y-m-d H:i:s'),
            closed_at: $order->getClosedAt()?->format('Y-m-d H:i:s'),
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
