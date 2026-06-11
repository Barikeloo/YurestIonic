<?php

declare(strict_types=1);

namespace App\Reporting\Application\ListScheduledReports;

final readonly class ListScheduledReportsResponse
{
    /**
     * @param array<int, array<string, mixed>> $reports
     */
    private function __construct(
        public array $reports,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $reports
     */
    public static function create(array $reports): self
    {
        return new self(reports: $reports);
    }

    public function toArray(): array
    {
        return $this->reports;
    }
}
