<?php

declare(strict_types=1);

namespace App\Sale\Application\GetFinalTicketPrint;

final readonly class GetFinalTicketPrintResponse
{
    private function __construct(
        public string $type,
        public string $ticketId,
        public string $orderId,
        public int $ticketNumber,
        public ?array $restaurant,
        public ?array $table,
        public int $totalConsumedCents,
        public int $totalPaidCents,
        public array $taxBreakdown,
        public array $paymentsSnapshot,
        public string $createdAt,
        public ?string $createdTime,
        public ?array $orderLines,
        public ?int $zReportNumber,
        public ?string $operator,
    ) {}

    public static function create(
        string $type,
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
        ?string $createdTime,
        ?array $orderLines,
        ?int $zReportNumber,
        ?string $operator,
    ): self {
        return new self(
            type: $type,
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
            createdTime: $createdTime,
            orderLines: $orderLines,
            zReportNumber: $zReportNumber,
            operator: $operator,
        );
    }

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
        ?string $createdTime,
        ?array $orderLines,
        ?int $zReportNumber,
        ?string $operator,
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
            createdTime: $createdTime,
            orderLines: $orderLines,
            zReportNumber: $zReportNumber,
            operator: $operator,
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
            'created_time' => $this->createdTime,
            'order_lines' => $this->orderLines,
            'z_report_number' => $this->zReportNumber,
            'operator' => $this->operator,
        ];
    }
}
