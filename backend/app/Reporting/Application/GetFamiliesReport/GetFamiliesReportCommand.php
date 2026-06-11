<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetFamiliesReport;

final readonly class GetFamiliesReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $period,
    ) {}
}
