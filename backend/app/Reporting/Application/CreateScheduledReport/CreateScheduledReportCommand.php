<?php

declare(strict_types=1);

namespace App\Reporting\Application\CreateScheduledReport;

final readonly class CreateScheduledReportCommand
{
    public function __construct(
        public int     $restaurantId,
        public string  $reportType,
        public string  $format,
        public string  $frequency,
        public string  $time,
        public ?int    $weekday,
        public ?int    $dayOfMonth,
        public array   $recipients,
        public string  $name,
        public bool    $active,
        public ?string $createdByUserUuid,
    ) {}
}
