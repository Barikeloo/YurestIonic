<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetTaxReport;

final readonly class GetTaxReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $period,
        public string $quarter,
    ) {}
}
