<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetEmployeesReport;

final readonly class GetEmployeesReportResponse
{
    private function __construct(
        private array  $items,
        private string $periodLabel,
        private array  $restaurant,
    ) {}

    public static function create(
        array  $items,
        string $periodLabel = '',
        array  $restaurant = [],
    ): self {
        return new self(
            items:       $items,
            periodLabel: $periodLabel,
            restaurant:  $restaurant,
        );
    }

    public function toArray(): array
    {
        return [
            'items'        => $this->items,
            'period_label' => $this->periodLabel,
            'restaurant'   => $this->restaurant,
        ];
    }
}
