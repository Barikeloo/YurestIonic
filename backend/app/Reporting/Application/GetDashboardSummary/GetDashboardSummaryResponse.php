<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetDashboardSummary;

final readonly class GetDashboardSummaryResponse
{
    private function __construct(
        private string $period,
        private string $dateLabel,
        private array  $kpis,
        private array  $sparks,
        private array  $byHour,
        private array  $byHourPrev,
        private array  $byDay,
        private array  $byFamily,
        private array  $topProducts,
        private array  $byPaymentMethod,
    ) {}

    public static function create(
        string $period,
        string $dateLabel,
        array  $kpis,
        array  $sparks,
        array  $byHour,
        array  $byHourPrev,
        array  $byDay,
        array  $byFamily,
        array  $topProducts,
        array  $byPaymentMethod,
    ): self {
        return new self(
            period:          $period,
            dateLabel:       $dateLabel,
            kpis:            $kpis,
            sparks:          $sparks,
            byHour:          $byHour,
            byHourPrev:      $byHourPrev,
            byDay:           $byDay,
            byFamily:        $byFamily,
            topProducts:     $topProducts,
            byPaymentMethod: $byPaymentMethod,
        );
    }

    public function toArray(): array
    {
        return [
            'period'            => $this->period,
            'date_label'        => $this->dateLabel,
            'kpis'              => $this->kpis,
            'sparks'            => $this->sparks,
            'by_hour'           => $this->byHour,
            'by_hour_prev'      => $this->byHourPrev,
            'by_day'            => $this->byDay,
            'by_family'         => $this->byFamily,
            'top_products'      => $this->topProducts,
            'by_payment_method' => $this->byPaymentMethod,
        ];
    }
}
