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
        public readonly string $status,
    ) {}

    public static function create(Sale $sale): self
    {
        return new self(
            id: $sale->id()->value(),
            uuid: $sale->uuid()->value(),
            restaurantId: $sale->restaurantId()->value(),
            orderId: $sale->orderId()->value(),
            openedByUserId: $sale->openedByUserId()->value(),
            closedByUserId: $sale->closedByUserId()?->value(),
            ticketNumber: $sale->ticketNumber()?->value(),
            valueDate: $sale->valueDate()->format('Y-m-d H:i:s'),
            total: $sale->total()->value(),
            status: $sale->status(),
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
            'status' => $this->status,
        ];
    }
}
