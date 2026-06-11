<?php

declare(strict_types=1);

namespace App\Reporting\Application\RecordReportExport;

final readonly class RecordReportExportCommand
{
    public function __construct(
        public int     $restaurantId,
        public ?string $userUuid,
        public string  $reportType,
        public string  $title,
        public string  $format,
        public string  $filename,
        public string  $contents,
    ) {}
}
