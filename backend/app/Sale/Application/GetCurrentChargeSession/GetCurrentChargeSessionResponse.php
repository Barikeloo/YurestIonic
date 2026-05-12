<?php

declare(strict_types=1);

namespace App\Sale\Application\GetCurrentChargeSession;

final readonly class GetCurrentChargeSessionResponse
{
    private function __construct(
        public array $data,
    ) {}

    public static function create(array $data): self
    {
        return new self(data: $data);
    }

    public static function fromPayload(array $data): self
    {
        return new self(data: $data);
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
