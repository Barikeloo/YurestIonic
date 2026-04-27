<?php

namespace App\Order\Domain\ValueObject;

final class OrderLineDiscountPercent
{
    private const MIN = 0;
    private const MAX = 100;

    private function __construct(
        private int $value,
    ) {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \InvalidArgumentException('Order line discount percent must be between 0 and 100.');
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
