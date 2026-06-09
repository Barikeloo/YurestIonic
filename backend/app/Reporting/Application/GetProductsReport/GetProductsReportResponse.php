<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetProductsReport;

final readonly class GetProductsReportResponse
{
    private function __construct(
        private int   $periodRevenue,
        private array $items,
        private array $stockCritical,
        private array $noSales7d,
        private int   $alertCount,
        private array $byZone,
    ) {}

    public static function create(
        int   $periodRevenue,
        array $items,
        array $stockCritical,
        array $noSales7d,
        int   $alertCount,
        array $byZone,
    ): self {
        return new self(
            periodRevenue: $periodRevenue,
            items:         $items,
            stockCritical: $stockCritical,
            noSales7d:     $noSales7d,
            alertCount:    $alertCount,
            byZone:        $byZone,
        );
    }

    public function toArray(): array
    {
        return [
            'period_revenue' => $this->periodRevenue,
            'items'          => $this->items,
            'stock_critical' => $this->stockCritical,
            'no_sales_7d'    => $this->noSales7d,
            'alert_count'    => $this->alertCount,
            'by_zone'        => $this->byZone,
        ];
    }
}
