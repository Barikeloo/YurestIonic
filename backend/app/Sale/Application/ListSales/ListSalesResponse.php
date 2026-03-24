<?php

namespace App\Sale\Application\ListSales;

use App\Sale\Domain\Entity\Sale;

final class ListSalesResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurant_id,
        public readonly string $order_id,
        public readonly string $user_id,
        public readonly ?int $ticket_number,
        public readonly string $value_date,
        public readonly int $total,
    ) {}

    public static function create(Sale $sale): self
    {
        return new self(
            id: $sale->getId()->value(),
            uuid: $sale->getUuid()->value(),
            restaurant_id: $sale->getRestaurantId()->value(),
            order_id: $sale->getOrderId()->value(),
            user_id: $sale->getUserId()->value(),
            ticket_number: $sale->getTicketNumber(),
            value_date: $sale->getValueDate()->format('Y-m-d H:i:s'),
            total: $sale->getTotal(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurant_id,
            'order_id' => $this->order_id,
            'user_id' => $this->user_id,
            'ticket_number' => $this->ticket_number,
            'value_date' => $this->value_date,
            'total' => $this->total,
        ];
    }
}
