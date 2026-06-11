<?php

declare(strict_types=1);

namespace App\Reporting\Application\DownloadReportExport;

final readonly class DownloadReportExportResponse
{
    private function __construct(
        public string $contents,
        public string $filename,
        public string $mimeType,
    ) {}

    public static function create(string $contents, string $filename, string $format): self
    {
        return new self(
            contents: $contents,
            filename: $filename,
            mimeType: $format === 'PDF' ? 'application/pdf' : 'text/csv',
        );
    }
}
