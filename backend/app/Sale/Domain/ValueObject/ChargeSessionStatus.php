<?php

declare(strict_types=1);

namespace App\Sale\Domain\ValueObject;

final class ChargeSessionStatus
{
    private const ACTIVE = 'active';

    private const COMPLETED = 'completed';

    private const CANCELLED = 'cancelled';

    private function __construct(private readonly string $value)
    {
        if (! in_array($value, [self::ACTIVE, self::COMPLETED, self::CANCELLED], true)) {
            throw new \InvalidArgumentException('Invalid charge session status: '.$value);
        }
    }

    public static function active(): self
    {
        return new self(self::ACTIVE);
    }

    public static function completed(): self
    {
        return new self(self::COMPLETED);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isActive(): bool
    {
        return $this->value === self::ACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }
}
