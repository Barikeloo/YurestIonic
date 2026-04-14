<?php

namespace App\Sale\Domain\ValueObject;

final class SaleTotal
{
    private function __construct(
        private int $value,
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Sale total cannot be negative.');
        }
    }

    public static function create(int $value): self
    {
        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }
}
