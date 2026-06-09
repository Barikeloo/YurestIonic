<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetDashboardSummary;

final readonly class GetDashboardSummaryCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $period,
    ) {}
}
