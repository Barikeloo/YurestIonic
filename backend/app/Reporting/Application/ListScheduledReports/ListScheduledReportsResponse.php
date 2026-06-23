<?php

declare(strict_types=1);

namespace App\Reporting\Application\ListScheduledReports;

final readonly class ListScheduledReportsResponse
{
    private function __construct(
        public array $reports,
    ) {}

    public static function create(array $reports): self
    {
        return new self(reports: $reports);
    }

    public function toArray(): array
    {
        return $this->reports;
    }
}
