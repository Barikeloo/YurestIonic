<?php

declare(strict_types=1);

namespace App\Sale\Application\GetPaymentTicket;

final class GetPaymentTicketResponse
{
    private function __construct(
        public readonly string $type,
        public readonly string $saleId,
        public readonly string $orderId,
        public readonly ?int $ticketNumber,
        public readonly ?array $restaurant,
        public readonly ?array $table,
        public readonly ?int $totalConsumedCents,
        public readonly int $totalPaidCents,
        public readonly ?int $remainingCents,
        public readonly array $payments,
        public readonly ?string $issuedAt,
        public readonly ?array $snapshot,
    ) {}

    public static function fromPayload(
        string $saleId,
        string $orderId,
        ?int $ticketNumber,
        ?array $restaurant,
        ?array $table,
        ?int $totalConsumedCents,
        int $totalPaidCents,
        ?int $remainingCents,
        array $payments,
        ?string $issuedAt,
        ?array $snapshot,
    ): self {
        return new self(
            type: 'payment',
            saleId: $saleId,
            orderId: $orderId,
            ticketNumber: $ticketNumber,
            restaurant: $restaurant,
            table: $table,
            totalConsumedCents: $totalConsumedCents,
            totalPaidCents: $totalPaidCents,
            remainingCents: $remainingCents,
            payments: $payments,
            issuedAt: $issuedAt,
            snapshot: $snapshot,
        );
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'sale_id' => $this->saleId,
            'order_id' => $this->orderId,
            'ticket_number' => $this->ticketNumber,
            'restaurant' => $this->restaurant,
            'table' => $this->table,
            'total_consumed_cents' => $this->totalConsumedCents,
            'total_paid_cents' => $this->totalPaidCents,
            'remaining_cents' => $this->remainingCents,
            'payments' => $this->payments,
            'issued_at' => $this->issuedAt,
            'snapshot' => $this->snapshot,
        ];
    }
}
