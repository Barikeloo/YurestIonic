<?php

namespace App\Sale\Domain\ValueObject;

final class SaleLineQuantity
{
    private function __construct(
        private int $value,
    ) {
        if ($value <= 0) {
            throw new \InvalidArgumentException('Sale line quantity must be greater than zero.');
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
