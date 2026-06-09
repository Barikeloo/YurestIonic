<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetProductsReport;

final readonly class GetProductsReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $period,
    ) {}
}
