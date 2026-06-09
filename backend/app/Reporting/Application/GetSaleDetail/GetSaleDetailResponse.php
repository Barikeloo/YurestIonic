<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetSaleDetail;

final readonly class GetSaleDetailResponse
{
    private function __construct(
        private array $data,
    ) {}

    public static function create(array $data): self
    {
        return new self(data: $data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
