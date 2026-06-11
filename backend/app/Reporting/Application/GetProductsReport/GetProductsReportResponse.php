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
        private string $periodLabel,
        private array $restaurant,
    ) {}

    public static function create(
        int   $periodRevenue,
        array $items,
        array $stockCritical,
        array $noSales7d,
        int   $alertCount,
        array $byZone,
        string $periodLabel,
        array $restaurant,
    ): self {
        return new self(
            periodRevenue: $periodRevenue,
            items:         $items,
            stockCritical: $stockCritical,
            noSales7d:     $noSales7d,
            alertCount:    $alertCount,
            byZone:        $byZone,
            periodLabel:   $periodLabel,
            restaurant:    $restaurant,
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
            'period_label'   => $this->periodLabel,
            'restaurant'     => $this->restaurant,
        ];
    }
}
