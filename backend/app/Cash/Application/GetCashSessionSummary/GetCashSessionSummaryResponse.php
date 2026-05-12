<?php

declare(strict_types=1);

namespace App\Cash\Application\GetCashSessionSummary;

final readonly class GetCashSessionSummaryResponse
{
    private function __construct(
        public string $id,
        public string $uuid,
        public string $status,
        public int $initialAmountCents,
        public int $totalSales,
        public int $totalCashPayments,
        public int $totalCardPayments,
        public int $totalBizumPayments,
        public int $totalOtherPayments,
        public int $totalInMovements,
        public int $totalOutMovements,
        public int $expectedAmount,
        public int $movementsCount,
        public int $paymentsCount,
        public int $ticketsCount,
        public int $dinersCount,
        public int $tipsCard,
    ) {}

    public static function create(
        string $id,
        string $uuid,
        string $status,
        int $initialAmountCents,
        int $totalSales,
        int $totalCashPayments,
        int $totalCardPayments,
        int $totalBizumPayments,
        int $totalOtherPayments,
        int $totalInMovements,
        int $totalOutMovements,
        int $expectedAmount,
        int $movementsCount,
        int $paymentsCount,
        int $ticketsCount,
        int $dinersCount,
        int $tipsCard,
    ): self {
        return new self(
            id: $id,
            uuid: $uuid,
            status: $status,
            initialAmountCents: $initialAmountCents,
            totalSales: $totalSales,
            totalCashPayments: $totalCashPayments,
            totalCardPayments: $totalCardPayments,
            totalBizumPayments: $totalBizumPayments,
            totalOtherPayments: $totalOtherPayments,
            totalInMovements: $totalInMovements,
            totalOutMovements: $totalOutMovements,
            expectedAmount: $expectedAmount,
            movementsCount: $movementsCount,
            paymentsCount: $paymentsCount,
            ticketsCount: $ticketsCount,
            dinersCount: $dinersCount,
            tipsCard: $tipsCard,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'initial_amount_cents' => $this->initialAmountCents,
            'total_sales' => $this->totalSales,
            'total_cash_payments' => $this->totalCashPayments,
            'total_card_payments' => $this->totalCardPayments,
            'total_bizum_payments' => $this->totalBizumPayments,
            'total_other_payments' => $this->totalOtherPayments,
            'total_in_movements' => $this->totalInMovements,
            'total_out_movements' => $this->totalOutMovements,
            'expected_amount' => $this->expectedAmount,
            'movements_count' => $this->movementsCount,
            'payments_count' => $this->paymentsCount,
            'tickets_count' => $this->ticketsCount,
            'diners_count' => $this->dinersCount,
            'tips_card' => $this->tipsCard,
        ];
    }
}
