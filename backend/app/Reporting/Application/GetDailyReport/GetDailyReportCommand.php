<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetDailyReport;

final readonly class GetDailyReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $period,
    ) {}
}
