<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetSalesReport;

final readonly class GetSalesReportResponse
{
    private function __construct(
        private array $data,
        private array $meta,
        private array $totals,
    ) {}

    public static function create(array $data, array $meta, array $totals): self
    {
        return new self(data: $data, meta: $meta, totals: $totals);
    }

    public function toArray(): array
    {
        return [
            'data'   => $this->data,
            'meta'   => $this->meta,
            'totals' => $this->totals,
        ];
    }
}
