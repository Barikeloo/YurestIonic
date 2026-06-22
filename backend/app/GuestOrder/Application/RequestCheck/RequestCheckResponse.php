<?php

declare(strict_types=1);

namespace App\GuestOrder\Application\RequestCheck;

final readonly class RequestCheckResponse
{
    private function __construct(
        public string $requestedAt,
    ) {}

    public static function create(string $requestedAt): self
    {
        return new self(requestedAt: $requestedAt);
    }

    public function toArray(): array
    {
        return ['requested_at' => $this->requestedAt];
    }
}
