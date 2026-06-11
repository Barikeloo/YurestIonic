<?php

declare(strict_types=1);

namespace App\Reporting\Application\ListReportExports;

final readonly class ListReportExportsCommand
{
    public function __construct(
        public int $restaurantId,
        public int $days = 30,
        public int $limit = 50,
    ) {}
}
