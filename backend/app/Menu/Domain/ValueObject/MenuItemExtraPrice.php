<?php

declare(strict_types=1);

namespace App\Menu\Domain\ValueObject;

final class MenuItemExtraPrice
{
    private int $value;

    private function __construct(int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('Menu item extra price must be >= 0.');
        }
        $this->value = $value;
    }

    public static function create(int $value): self
    {
        return new self($value);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function isZero(): bool
    {
        return $this->value === 0;
    }
}
