<?php

namespace App\Order\Domain\ValueObject;

final class OrderLineDinerNumber
{
    private const MIN = 1;

    private function __construct(
        private int $value,
    ) {
        if ($value < self::MIN) {
            throw new \InvalidArgumentException('Order line diner number must be greater than or equal to 1.');
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
