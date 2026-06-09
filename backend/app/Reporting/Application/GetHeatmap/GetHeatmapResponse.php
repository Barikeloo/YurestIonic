<?php

declare(strict_types=1);

namespace App\Reporting\Application\GetHeatmap;

final readonly class GetHeatmapResponse
{
    public function __construct(
        public array $data,
    ) {}

    public static function create(array $data): self
    {
        return new self($data);
    }

    public function toArray(): array
    {
        return ['data' => $this->data];
    }
}
