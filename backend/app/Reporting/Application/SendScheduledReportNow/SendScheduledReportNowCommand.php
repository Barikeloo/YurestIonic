<?php

declare(strict_types=1);

namespace App\Reporting\Application\SendScheduledReportNow;

final readonly class SendScheduledReportNowCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $uuid,
    ) {}
}
