<?php

namespace App\Sale\Application\GetSale;

use App\Sale\Domain\Entity\Sale;

final class GetSaleResponse
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
            id: $sale->getId()->value(),
            uuid: $sale->getUuid()->value(),
            restaurant_id: $sale->getRestaurantId()->value(),
            order_id: $sale->getOrderId()->value(),
            opened_by_user_id: $sale->getOpenedByUserId()->value(),
            closed_by_user_id: $sale->getClosedByUserId()?->value(),
            ticket_number: $sale->getTicketNumber()?->value(),
            value_date: $sale->getValueDate()->format('Y-m-d H:i:s'),
            total: $sale->getTotal()->value(),
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
