<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

final class Category
{
    private const VALID = ['order', 'caja', 'sale', 'table', 'catalog', 'auth', 'config', 'system'];

    private function __construct(private readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid audit category: {$value}");
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Category $other): bool
    {
        return $this->value === $other->value;
    }
}
