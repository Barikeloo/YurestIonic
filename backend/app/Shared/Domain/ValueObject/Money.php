<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

/**
 * Monetary amount in cents. May be negative (for credit notes, discrepancies,
 * deltas...). VOs that require non-negative semantics (prices, quantities)
 * must validate at their own boundary, not rely on Money.
 */
final class Money
{
    private function __construct(private readonly int $cents) {}

    public static function create(int $cents): self
    {
        return new self($cents);
    }

    public static function fromEuros(float $euros): self
    {
        return new self((int) round($euros * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function toCents(): int
    {
        return $this->cents;
    }

    public function toEuros(): float
    {
        return $this->cents / 100;
    }

    public function add(Money $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function subtract(Money $other): self
    {
        return new self($this->cents - $other->cents);
    }

    public function negate(): self
    {
        return new self(-$this->cents);
    }

    public function abs(): self
    {
        return new self(abs($this->cents));
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function isPositive(): bool
    {
        return $this->cents > 0;
    }

    public function isNegative(): bool
    {
        return $this->cents < 0;
    }

    public function isGreaterThan(Money $other): bool
    {
        return $this->cents > $other->cents;
    }

    public function isLessThan(Money $other): bool
    {
        return $this->cents < $other->cents;
    }

    public function equals(Money $other): bool
    {
        return $this->cents === $other->cents;
    }
}
