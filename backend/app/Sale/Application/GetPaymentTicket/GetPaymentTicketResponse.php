<?php

declare(strict_types=1);

namespace App\Sale\Application\GetPaymentTicket;

final readonly class GetPaymentTicketResponse
{
    private function __construct(
        public string $type,
        public string $saleId,
        public string $orderId,
        public ?int $ticketNumber,
        public ?array $restaurant,
        public ?array $table,
        public ?int $totalConsumedCents,
        public int $totalPaidCents,
        public ?int $remainingCents,
        public array $payments,
        public ?string $issuedAt,
        public ?string $issuedTime,
        public ?array $lines,
        public ?array $taxBreakdown,
        public ?int $zReportNumber,
        public ?string $operator,
        public ?array $snapshot,
    ) {}

    public static function create(
        string $type,
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
        ?string $issuedTime,
        ?array $lines,
        ?array $taxBreakdown,
        ?int $zReportNumber,
        ?string $operator,
        ?array $snapshot,
    ): self {
        return new self(
            type: $type,
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
            issuedTime: $issuedTime,
            lines: $lines,
            taxBreakdown: $taxBreakdown,
            zReportNumber: $zReportNumber,
            operator: $operator,
            snapshot: $snapshot,
        );
    }

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
        ?string $issuedTime,
        ?array $lines,
        ?array $taxBreakdown,
        ?int $zReportNumber,
        ?string $operator,
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
            issuedTime: $issuedTime,
            lines: $lines,
            taxBreakdown: $taxBreakdown,
            zReportNumber: $zReportNumber,
            operator: $operator,
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
            'issued_time' => $this->issuedTime,
            'lines' => $this->lines,
            'tax_breakdown' => $this->taxBreakdown,
            'z_report_number' => $this->zReportNumber,
            'operator' => $this->operator,
            'snapshot' => $this->snapshot,
        ];
    }
}
