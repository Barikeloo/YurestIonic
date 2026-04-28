<?php

declare(strict_types=1);

namespace App\Cash\Domain\ValueObject;

final class TipSource
{
    private const VALID_SOURCES = ['card_added', 'cash_declared'];

    private function __construct(private readonly string $value)
    {
        if (! in_array($value, self::VALID_SOURCES, true)) {
            throw new \InvalidArgumentException("Invalid tip source: {$value}");
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function cardAdded(): self
    {
        return new self('card_added');
    }

    public static function cashDeclared(): self
    {
        return new self('cash_declared');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isCardAdded(): bool
    {
        return $this->value === 'card_added';
    }

    public function isCashDeclared(): bool
    {
        return $this->value === 'cash_declared';
    }

    public function equals(TipSource $other): bool
    {
        return $this->value === $other->value;
    }
}
