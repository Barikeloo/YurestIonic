<?php

declare(strict_types=1);

namespace App\Cash\Application\GetZReport;

use App\Cash\Domain\Entity\ZReport;

final readonly class GetZReportResponse
{
    private function __construct(
        public string $id,
        public string $cashSessionId,
        public int $reportNumber,
        public string $reportHash,
        public int $totalSalesCents,
        public int $totalCashCents,
        public int $totalCardCents,
        public int $totalOtherCents,
        public int $cashInCents,
        public int $cashOutCents,
        public int $tipsCents,
        public int $discrepancyCents,
        public int $salesCount,
        public int $cancelledSalesCount,
        public string $generatedAt,
    ) {}

    public static function create(
        string $id,
        string $cashSessionId,
        int $reportNumber,
        string $reportHash,
        int $totalSalesCents,
        int $totalCashCents,
        int $totalCardCents,
        int $totalOtherCents,
        int $cashInCents,
        int $cashOutCents,
        int $tipsCents,
        int $discrepancyCents,
        int $salesCount,
        int $cancelledSalesCount,
        string $generatedAt,
    ): self {
        return new self(
            id: $id,
            cashSessionId: $cashSessionId,
            reportNumber: $reportNumber,
            reportHash: $reportHash,
            totalSalesCents: $totalSalesCents,
            totalCashCents: $totalCashCents,
            totalCardCents: $totalCardCents,
            totalOtherCents: $totalOtherCents,
            cashInCents: $cashInCents,
            cashOutCents: $cashOutCents,
            tipsCents: $tipsCents,
            discrepancyCents: $discrepancyCents,
            salesCount: $salesCount,
            cancelledSalesCount: $cancelledSalesCount,
            generatedAt: $generatedAt,
        );
    }

    public static function fromZReport(ZReport $zReport): self
    {
        return self::create(
            id: $zReport->id()->value(),
            cashSessionId: $zReport->cashSessionId()->value(),
            reportNumber: $zReport->reportNumber()->value(),
            reportHash: $zReport->reportHash()->value(),
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
