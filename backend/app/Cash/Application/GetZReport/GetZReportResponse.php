<?php

declare(strict_types=1);

namespace App\Cash\Application\GetZReport;

use App\Cash\Domain\Entity\ZReport;

final class GetZReportResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $cashSessionId,
        public readonly int $reportNumber,
        public readonly string $reportHash,
        public readonly int $totalSalesCents,
        public readonly int $totalCashCents,
        public readonly int $totalCardCents,
        public readonly int $totalOtherCents,
        public readonly int $cashInCents,
        public readonly int $cashOutCents,
        public readonly int $tipsCents,
        public readonly int $discrepancyCents,
        public readonly int $salesCount,
        public readonly int $cancelledSalesCount,
        public readonly string $generatedAt,
    ) {}

    public static function create(ZReport $zReport): self
    {
        return new self(
            id: $zReport->id()->value(),
            cashSessionId: $zReport->cashSessionId()->value(),
            reportNumber: $zReport->reportNumber(),
            reportHash: $zReport->reportHash(),
            totalSalesCents: $zReport->totalSales()->toCents(),
            totalCashCents: $zReport->totalCash()->toCents(),
            totalCardCents: $zReport->totalCard()->toCents(),
            totalOtherCents: $zReport->totalOther()->toCents(),
            cashInCents: $zReport->cashIn()->toCents(),
            cashOutCents: $zReport->cashOut()->toCents(),
            tipsCents: $zReport->tips()->toCents(),
            discrepancyCents: $zReport->discrepancy()->toCents(),
            salesCount: $zReport->salesCount(),
            cancelledSalesCount: $zReport->cancelledSalesCount(),
            generatedAt: $zReport->generatedAt()->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'cash_session_id' => $this->cashSessionId,
            'report_number' => $this->reportNumber,
            'report_hash' => $this->reportHash,
            'total_sales_cents' => $this->totalSalesCents,
            'total_cash_cents' => $this->totalCashCents,
            'total_card_cents' => $this->totalCardCents,
            'total_other_cents' => $this->totalOtherCents,
            'cash_in_cents' => $this->cashInCents,
            'cash_out_cents' => $this->cashOutCents,
            'tips_cents' => $this->tipsCents,
            'discrepancy_cents' => $this->discrepancyCents,
            'sales_count' => $this->salesCount,
            'cancelled_sales_count' => $this->cancelledSalesCount,
            'generated_at' => $this->generatedAt,
        ];
    }
}
