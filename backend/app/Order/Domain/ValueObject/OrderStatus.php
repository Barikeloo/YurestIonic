<?php

namespace App\Order\Domain\ValueObject;

use InvalidArgumentException;

final class OrderStatus
{
    private const OPEN = 'open';
    private const CANCELLED = 'cancelled';
    private const INVOICED = 'invoiced';

    private function __construct(private readonly string $value)
    {
    }

    public static function create(string $value): self
    {
        $value = strtolower($value);

        if (! in_array($value, [self::OPEN, self::CANCELLED, self::INVOICED], true)) {
            throw new InvalidArgumentException("Estado de orden inválido: {$value}");
        }

        return new self($value);
    }

    public static function open(): self
    {
        return new self(self::OPEN);
    }

    public static function cancelled(): self
    {
        return new self(self::CANCELLED);
    }

    public static function invoiced(): self
    {
        return new self(self::INVOICED);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isOpen(): bool
    {
        return $this->value === self::OPEN;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function isInvoiced(): bool
    {
        return $this->value === self::INVOICED;
    }
}
