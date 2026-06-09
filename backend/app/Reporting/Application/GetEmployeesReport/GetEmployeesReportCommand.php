<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetEmployeesReport;

final readonly class GetEmployeesReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $period,
    ) {}
}
