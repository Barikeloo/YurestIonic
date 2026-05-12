<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateSale;

use App\Sale\Domain\Entity\Sale;

final readonly class CreateSaleResponse
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
        public string $status,
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
        string $status,
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
            status: $status,
        );
    }

    public static function fromSale(Sale $sale): self
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
            status: $sale->status()->value(),
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
