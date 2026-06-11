<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetDailyReport;

final readonly class GetDailyReportResponse
{
    private function __construct(
        private array  $restaurant,
        private string $periodLabel,
        private array  $kpis,
        private array  $byFamily,
        private array  $topProducts,
        private array  $byPaymentMethod,
    ) {}

    public static function create(
        array  $restaurant,
        string $periodLabel,
        array  $kpis,
        array  $byFamily,
        array  $topProducts,
        array  $byPaymentMethod,
    ): self {
        return new self(
            restaurant:      $restaurant,
            periodLabel:     $periodLabel,
            kpis:            $kpis,
            byFamily:        $byFamily,
            topProducts:     $topProducts,
            byPaymentMethod: $byPaymentMethod,
        );
    }

    public function toArray(): array
    {
        return [
            'restaurant'        => $this->restaurant,
            'period_label'      => $this->periodLabel,
            'kpis'              => $this->kpis,
            'by_family'         => $this->byFamily,
            'top_products'      => $this->topProducts,
            'by_payment_method' => $this->byPaymentMethod,
        ];
    }
}
