<?php

namespace App\Tax\Domain\ValueObject;

class TaxPercentage
{
    private const MIN = 0;

    private const MAX = 100;

    private int $value;

    private function __construct(int $value)
    {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \InvalidArgumentException(
                sprintf('Tax percentage must be between %d and %d.', self::MIN, self::MAX)
            );
        }

        $this->value = $value;
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
