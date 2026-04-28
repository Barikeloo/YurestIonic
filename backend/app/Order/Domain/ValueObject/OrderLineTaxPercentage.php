<?php

namespace App\Order\Domain\ValueObject;

final class OrderLineTaxPercentage
{
    private const MIN = 0;

    private const MAX = 100;

    private function __construct(
        private int $value,
    ) {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \InvalidArgumentException(
                sprintf('Order line tax percentage must be between %d and %d.', self::MIN, self::MAX)
            );
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
