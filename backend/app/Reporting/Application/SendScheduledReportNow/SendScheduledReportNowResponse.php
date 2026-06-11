<?php

declare(strict_types=1);

namespace App\Reporting\Application\SendScheduledReportNow;

final readonly class SendScheduledReportNowResponse
{
    private function __construct(
        public string $uuid,
        public string $reportName,
    ) {}

    public static function create(string $uuid, string $reportName): self
    {
        return new self(uuid: $uuid, reportName: $reportName);
    }

    public function toArray(): array
    {
        return [
            'uuid'        => $this->uuid,
            'report_name' => $this->reportName,
        ];
    }
}
