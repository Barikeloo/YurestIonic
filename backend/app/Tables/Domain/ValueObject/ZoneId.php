<?php

namespace App\Tables\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Uuid;

final class ZoneId
{
    private function __construct(
        private Uuid $value,
    ) {}

    public static function create(string $value): self
    {
        return new self(Uuid::create($value));
    }

    public function value(): string
    {
        return $this->value->value();
    }
}
