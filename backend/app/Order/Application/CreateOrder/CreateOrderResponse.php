<?php

namespace App\Order\Application\CreateOrder;

use App\Order\Domain\Entity\Order;

final class CreateOrderResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurantId,
        public readonly string $tableId,
        public readonly string $openedByUserId,
        public readonly string $status,
        public readonly int $diners,
        public readonly string $openedAt,
    ) {}

    public static function create(Order $order): self
    {
        return new self(
            id: $order->id()->value(),
            uuid: $order->uuid()->value(),
            restaurantId: $order->restaurantId()->value(),
            tableId: $order->tableId()->value(),
            openedByUserId: $order->openedByUserId()->value(),
            status: $order->status()->value(),
            diners: $order->diners()->value(),
            openedAt: $order->openedAt()?->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurantId,
            'table_id' => $this->tableId,
            'opened_by_user_id' => $this->openedByUserId,
            'status' => $this->status,
            'diners' => $this->diners,
            'opened_at' => $this->openedAt,
        ];
    }
}
