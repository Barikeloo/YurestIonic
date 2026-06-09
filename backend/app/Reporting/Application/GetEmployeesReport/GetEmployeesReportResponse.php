<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetEmployeesReport;

final readonly class GetEmployeesReportResponse
{
    private function __construct(private array $items) {}

    public static function create(array $items): self
    {
        return new self(items: $items);
    }

    public function toArray(): array
    {
        return ['items' => $this->items];
    }
}
