<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetCashReport;

final readonly class GetCashReportResponse
{
    private function __construct(
        private array  $restaurant,
        private string $periodLabel,
        private array  $sessions,
        private array  $movements,
        private int    $totalIn,
        private int    $totalOut,
        private int    $net,
        private int    $discrepancyTotal,
    ) {}

    public static function create(
        array  $restaurant,
        string $periodLabel,
        array  $sessions,
        array  $movements,
        int    $totalIn,
        int    $totalOut,
        int    $net,
        int    $discrepancyTotal,
    ): self {
        return new self(
            restaurant:       $restaurant,
            periodLabel:      $periodLabel,
            sessions:         $sessions,
            movements:        $movements,
            totalIn:          $totalIn,
            totalOut:         $totalOut,
            net:              $net,
            discrepancyTotal: $discrepancyTotal,
        );
    }

    public function toArray(): array
    {
        return [
            'restaurant'        => $this->restaurant,
            'period_label'      => $this->periodLabel,
            'sessions'          => $this->sessions,
            'movements'         => $this->movements,
            'total_in'          => $this->totalIn,
            'total_out'         => $this->totalOut,
            'net'               => $this->net,
            'discrepancy_total' => $this->discrepancyTotal,
        ];
    }
}
