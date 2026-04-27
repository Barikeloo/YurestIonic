<?php

declare(strict_types=1);

namespace App\Cash\Application\GetCashSessionSummary;

use App\Cash\Domain\Entity\CashSession;

final class GetCashSessionSummaryResponse
{
    private function __construct(
        public readonly string $id,
        public readonly string $uuid,
        public readonly string $status,
        public readonly int $initialAmountCents,
        public readonly int $totalSales,
        public readonly int $totalCashPayments,
        public readonly int $totalCardPayments,
        public readonly int $totalBizumPayments,
        public readonly int $totalOtherPayments,
        public readonly int $totalInMovements,
        public readonly int $totalOutMovements,
        public readonly int $expectedAmount,
        public readonly int $movementsCount,
        public readonly int $paymentsCount,
        public readonly int $ticketsCount,
        public readonly int $dinersCount,
        public readonly int $tipsCard,
    ) {}

    public static function create(
        CashSession $cashSession,
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
            id: $cashSession->id()->value(),
            uuid: $cashSession->uuid()->value(),
            status: $cashSession->status()->value(),
            initialAmountCents: $cashSession->initialAmount()->toCents(),
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
