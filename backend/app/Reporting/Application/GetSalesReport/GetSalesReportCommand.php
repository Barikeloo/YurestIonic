<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetSalesReport;

final readonly class GetSalesReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $period,
        public int    $page,
        public int    $perPage,
    ) {}
}
