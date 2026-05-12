<?php

namespace App\Sale\Application\ListSales;

use App\Sale\Domain\Entity\Sale;

final readonly class ListSalesResponse
{
    private function __construct(
        public string $id,
        public string $uuid,
        public string $restaurantId,
        public string $orderId,
        public string $openedByUserId,
        public ?string $closedByUserId,
        public ?int $ticketNumber,
        public string $valueDate,
        public int $total,
    ) {}

    public static function create(
        string $id,
        string $uuid,
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        ?string $closedByUserId,
        ?int $ticketNumber,
        string $valueDate,
        int $total,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            restaurantId: $restaurantId,
            orderId: $orderId,
            openedByUserId: $openedByUserId,
            closedByUserId: $closedByUserId,
            ticketNumber: $ticketNumber,
            valueDate: $valueDate,
            total: $total,
        );
    }

    public static function fromSale(Sale $sale): self
    {
        return self::create(
            id: $sale->id()->value(),
            uuid: $sale->uuid()->value(),
            restaurantId: $sale->restaurantId()->value(),
            orderId: $sale->orderId()->value(),
            openedByUserId: $sale->openedByUserId()->value(),
            closedByUserId: $sale->closedByUserId()?->value(),
            ticketNumber: $sale->ticketNumber()?->value(),
            valueDate: $sale->valueDate()->format('Y-m-d H:i:s'),
            total: $sale->total()->value(),
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
