<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetCashReport;

final readonly class GetCashReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $period,
    ) {}
}
