<?php

namespace App\Order\Domain\ValueObject;

final class OrderLinePrice
{
    private function __construct(
        private int $value,
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('Order line price cannot be negative.');
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