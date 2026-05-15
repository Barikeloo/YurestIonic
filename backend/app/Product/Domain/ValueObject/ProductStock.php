<?php

namespace App\Product\Domain\ValueObject;

class ProductStock
{
    private const MIN = 0;

    private int $value;

    private function __construct(int $value)
    {
        if ($value < self::MIN) {
            throw new \InvalidArgumentException('Product stock must be greater than or equal to 0.');
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

    public function isSufficientFor(int $quantity): bool
    {
        return $this->value >= $quantity;
    }

    public function decrease(int $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be greater than or equal to 0.');
        }

        return self::create($this->value - $amount);
    }

    public function increase(int $amount): self
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount must be greater than or equal to 0.');
        }

        return self::create($this->value + $amount);
    }
}
