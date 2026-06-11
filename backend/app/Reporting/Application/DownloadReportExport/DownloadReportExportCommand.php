<?php

declare(strict_types=1);

namespace App\Reporting\Application\DownloadReportExport;

final readonly class DownloadReportExportCommand
{
    public function __construct(
        public int    $restaurantId,
        public string $uuid,
    ) {}
}
