<?php

declare(strict_types=1);

namespace App\Reporting\Application\CreateScheduledReport;

final readonly class CreateScheduledReportResponse
{
    private function __construct(
        public string $uuid,
    ) {}

    public static function create(string $uuid): self
    {
        return new self(uuid: $uuid);
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
        ];
    }
}
