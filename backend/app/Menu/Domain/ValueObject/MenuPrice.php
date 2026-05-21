<?php

declare(strict_types=1);

namespace App\Menu\Domain\ValueObject;

final class MenuPrice
{
    private const MIN = 0;

    private int $value;

    private function __construct(int $value)
    {
        if ($value < self::MIN) {
            throw new \InvalidArgumentException('Menu price must be greater than or equal to 0.');
        }

        $this->value = $value;
    }

    public static function create(int $value): self
    {
        return new self($value);
    }

    /** Céntimos */
    public function value(): int
    {
        return $this->value;
    }
}
