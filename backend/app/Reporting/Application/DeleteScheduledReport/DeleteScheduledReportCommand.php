<?php

declare(strict_types=1);

namespace App\Reporting\Application\DeleteScheduledReport;

final readonly class DeleteScheduledReportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $uuid,
    ) {}
}
