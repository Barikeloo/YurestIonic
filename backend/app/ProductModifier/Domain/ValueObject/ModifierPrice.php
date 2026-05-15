<?php

namespace App\ProductModifier\Domain\ValueObject;

class ModifierPrice
{
    private const MIN = 0;

    private int $value;

    private function __construct(int $value)
    {
        if ($value < self::MIN) {
            throw new \InvalidArgumentException('Modifier price must be greater than or equal to 0.');
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
