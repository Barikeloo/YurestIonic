<?php

declare(strict_types=1);

namespace App\Cash\Application\StartClosingCashSession;

final readonly class StartClosingCashSessionResponse
{
    private function __construct(
        public string $id,
        public string $status,
    ) {}

    public static function create(
        string $id,
        string $status,
    ): self {
        return new self(
            id: $id,
            status: $status,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
        ];
    }
}
