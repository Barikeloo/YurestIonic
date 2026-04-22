<?php

declare(strict_types=1);

namespace App\Cash\Domain\ValueObject;

final class MovementType
{
    private const VALID_TYPES = ['in', 'out'];

    private function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid movement type: {$value}");
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function in(): self
    {
        return new self('in');
    }

    public static function out(): self
    {
        return new self('out');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isIn(): bool
    {
        return $this->value === 'in';
    }

    public function isOut(): bool
    {
        return $this->value === 'out';
    }

    public function equals(MovementType $other): bool
    {
        return $this->value === $other->value;
    }
}
