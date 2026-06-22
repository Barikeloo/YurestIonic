<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\DeletePendingLine;

final readonly class DeletePendingLineResponse
{
    private function __construct(
        public string $lineId,
    ) {}

    public static function create(string $lineId): self
    {
        return new self(lineId: $lineId);
    }

    public function toArray(): array
    {
        return ['line_id' => $this->lineId];
    }
}
