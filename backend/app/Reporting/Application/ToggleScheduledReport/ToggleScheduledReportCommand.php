<?php

declare(strict_types=1);

namespace App\Reporting\Application\ToggleScheduledReport;

final readonly class ToggleScheduledReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $uuid,
    ) {}
}
