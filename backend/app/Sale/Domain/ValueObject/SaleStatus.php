<?php

declare(strict_types=1);

namespace App\Sale\Domain\ValueObject;

final class SaleStatus
{
    private const VALID_STATUSES = ['open', 'closed', 'cancelled', 'pending'];

    private function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid sale status: {$value}");
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function open(): self
    {
        return new self('open');
    }

    public static function closed(): self
    {
        return new self('closed');
    }

    public static function cancelled(): self
    {
        return new self('cancelled');
    }

    public static function pending(): self
    {
        return new self('pending');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isOpen(): bool
    {
        return $this->value === 'open';
    }

    public function isClosed(): bool
    {
        return $this->value === 'closed';
    }

    public function isCancelled(): bool
    {
        return $this->value === 'cancelled';
    }

    public function isPending(): bool
    {
        return $this->value === 'pending';
    }

    public function equals(SaleStatus $other): bool
    {
        return $this->value === $other->value;
    }
}
