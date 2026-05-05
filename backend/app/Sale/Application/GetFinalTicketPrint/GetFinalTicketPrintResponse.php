<?php

declare(strict_types=1);

namespace App\Sale\Application\GetFinalTicketPrint;

final class GetFinalTicketPrintResponse
{
    private function __construct(
        public readonly string $type,
        public readonly string $ticketId,
        public readonly string $orderId,
        public readonly int $ticketNumber,
        public readonly ?array $restaurant,
        public readonly ?array $table,
        public readonly int $totalConsumedCents,
        public readonly int $totalPaidCents,
        public readonly array $taxBreakdown,
        public readonly array $paymentsSnapshot,
        public readonly string $createdAt,
    ) {}

    public static function fromPayload(
        string $ticketId,
        string $orderId,
        int $ticketNumber,
        ?array $restaurant,
        ?array $table,
        int $totalConsumedCents,
        int $totalPaidCents,
        array $taxBreakdown,
        array $paymentsSnapshot,
        string $createdAt,
    ): self {
        return new self(
            type: 'final',
            ticketId: $ticketId,
            orderId: $orderId,
            ticketNumber: $ticketNumber,
            restaurant: $restaurant,
            table: $table,
            totalConsumedCents: $totalConsumedCents,
            totalPaidCents: $totalPaidCents,
            taxBreakdown: $taxBreakdown,
            paymentsSnapshot: $paymentsSnapshot,
            createdAt: $createdAt,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'ticket_id' => $this->ticketId,
            'order_id' => $this->orderId,
            'ticket_number' => $this->ticketNumber,
            'restaurant' => $this->restaurant,
            'table' => $this->table,
            'total_consumed_cents' => $this->totalConsumedCents,
            'total_paid_cents' => $this->totalPaidCents,
            'tax_breakdown' => $this->taxBreakdown,
            'payments_snapshot' => $this->paymentsSnapshot,
            'created_at' => $this->createdAt,
        ];
    }
}
