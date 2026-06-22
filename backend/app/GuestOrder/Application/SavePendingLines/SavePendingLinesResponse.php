<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\SavePendingLines;

final readonly class SavePendingLinesResponse
{
    private function __construct(
        public array $lineIds,
    ) {}

    public static function create(array $lineIds): self
    {
        return new self(lineIds: $lineIds);
    }

    public function toArray(): array
    {
        return ['line_ids' => $this->lineIds];
    }
}
