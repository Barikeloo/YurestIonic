<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class Money
{
    private function __construct(private readonly int $cents)
    {
        if ($cents < 0) {
            throw new \InvalidArgumentException('Money cannot be negative');
        }
    }

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
        $result = $this->cents - $other->cents;
        if ($result < 0) {
            throw new \InvalidArgumentException('Subtraction would result in negative money');
        }
        return new self($result);
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
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
