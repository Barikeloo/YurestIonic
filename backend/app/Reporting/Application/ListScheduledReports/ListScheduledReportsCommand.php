<?php

declare(strict_types=1);

namespace App\Reporting\Application\ListScheduledReports;

final readonly class ListScheduledReportsCommand
{
    public function __construct(
        public int $restaurantId,
    ) {}
}
