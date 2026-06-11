<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetFamiliesReport;

final readonly class GetFamiliesReportResponse
{
    private function __construct(
        private array  $restaurant,
        private string $periodLabel,
        private array  $families,
        private int    $total,
        private int    $prevTotal,
    ) {}

    public static function create(
        array  $restaurant,
        string $periodLabel,
        array  $families,
        int    $total,
        int    $prevTotal,
    ): self {
        return new self(
            restaurant:  $restaurant,
            periodLabel: $periodLabel,
            families:    $families,
            total:       $total,
            prevTotal:   $prevTotal,
        );
    }

    public function toArray(): array
    {
        return [
            'restaurant'   => $this->restaurant,
            'period_label' => $this->periodLabel,
            'families'     => $this->families,
            'total'        => $this->total,
            'prev_total'   => $this->prevTotal,
        ];
    }
}
