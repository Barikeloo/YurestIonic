<?php

namespace App\Sale\Application\UpdateSale;

use App\Sale\Domain\Entity\Sale;

final class UpdateSaleResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurant_id,
        public readonly string $order_id,
        public readonly string $opened_by_user_id,
        public readonly ?string $closed_by_user_id,
        public readonly ?int $ticket_number,
        public readonly string $value_date,
        public readonly int $total,
    ) {}

    public static function create(Sale $sale): self
    {
        return new self(
            id: $sale->id()->value(),
            uuid: $sale->uuid()->value(),
            restaurant_id: $sale->restaurantId()->value(),
            order_id: $sale->orderId()->value(),
            opened_by_user_id: $sale->openedByUserId()->value(),
            closed_by_user_id: $sale->closedByUserId()?->value(),
            ticket_number: $sale->ticketNumber()?->value(),
            value_date: $sale->valueDate()->format('Y-m-d H:i:s'),
            total: $sale->total()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurant_id,
            'order_id' => $this->order_id,
            'opened_by_user_id' => $this->opened_by_user_id,
            'closed_by_user_id' => $this->closed_by_user_id,
            'ticket_number' => $this->ticket_number,
            'value_date' => $this->value_date,
            'total' => $this->total,
        ];
    }
}
