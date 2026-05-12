<?php

declare(strict_types=1);

namespace App\Sale\Application\GetOrderFinalTicket;

use App\Sale\Domain\Entity\OrderFinalTicket;

final readonly class GetOrderFinalTicketResponse
{
    private function __construct(
        public string $id,
        public string $restaurantId,
        public string $orderId,
        public string $closedByUserId,
        public int $ticketNumber,
        public int $totalConsumedCents,
        public int $totalPaidCents,
        public array $paymentsSnapshot,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function create(
        string $id,
        string $restaurantId,
        string $orderId,
        string $closedByUserId,
        int $ticketNumber,
        int $totalConsumedCents,
        int $totalPaidCents,
        array $paymentsSnapshot,
        string $createdAt,
        string $updatedAt,
    ): self {
        return new self(
            id: $id,
            restaurantId: $restaurantId,
            orderId: $orderId,
            closedByUserId: $closedByUserId,
            ticketNumber: $ticketNumber,
            totalConsumedCents: $totalConsumedCents,
            totalPaidCents: $totalPaidCents,
            paymentsSnapshot: $paymentsSnapshot,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public static function fromEntity(OrderFinalTicket $ticket): self
    {
        return new self(
            id: $ticket->id()->value(),
            restaurantId: $ticket->restaurantId()->value(),
            orderId: $ticket->orderId()->value(),
            closedByUserId: $ticket->closedByUserId()->value(),
            ticketNumber: $ticket->ticketNumber(),
            totalConsumedCents: $ticket->totalConsumedCents(),
            totalPaidCents: $ticket->totalPaidCents(),
            paymentsSnapshot: $ticket->paymentsSnapshot(),
            createdAt: $ticket->createdAt()->format(\DateTimeInterface::ATOM),
            updatedAt: $ticket->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'restaurant_id' => $this->restaurantId,
            'order_id' => $this->orderId,
            'closed_by_user_id' => $this->closedByUserId,
            'ticket_number' => $this->ticketNumber,
            'total_consumed_cents' => $this->totalConsumedCents,
            'total_paid_cents' => $this->totalPaidCents,
            'payments_snapshot' => $this->paymentsSnapshot,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
