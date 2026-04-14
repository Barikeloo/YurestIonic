<?php

namespace App\Sale\Application\CreateSale;

use App\Sale\Domain\Entity\Sale;

final class CreateSaleResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $restaurantId,
        public readonly string $orderId,
        public readonly string $openedByUserId,
        public readonly ?string $closedByUserId,
        public readonly ?int $ticketNumber,
        public readonly string $valueDate,
        public readonly int $total,
    ) {}

    public static function create(Sale $sale): self
    {
        return new self(
            id: $sale->getId()->value(),
            uuid: $sale->getUuid()->value(),
            restaurantId: $sale->getRestaurantId()->value(),
            orderId: $sale->getOrderId()->value(),
            openedByUserId: $sale->getOpenedByUserId()->value(),
            closedByUserId: $sale->getClosedByUserId()?->value(),
            ticketNumber: $sale->getTicketNumber()?->value(),
            valueDate: $sale->getValueDate()->format('Y-m-d H:i:s'),
            total: $sale->getTotal()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'restaurant_id' => $this->restaurantId,
            'order_id' => $this->orderId,
            'opened_by_user_id' => $this->openedByUserId,
            'closed_by_user_id' => $this->closedByUserId,
            'ticket_number' => $this->ticketNumber,
            'value_date' => $this->valueDate,
            'total' => $this->total,
        ];
    }
}
