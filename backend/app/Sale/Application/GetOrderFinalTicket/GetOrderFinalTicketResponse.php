<?php

declare(strict_types=1);

namespace App\Sale\Application\GetOrderFinalTicket;

use App\Sale\Domain\Entity\OrderFinalTicket;

final class GetOrderFinalTicketResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $restaurantId,
        public readonly string $orderId,
        public readonly string $closedByUserId,
        public readonly int $ticketNumber,
        public readonly int $totalConsumedCents,
        public readonly int $totalPaidCents,
        public readonly array $paymentsSnapshot,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {}

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
