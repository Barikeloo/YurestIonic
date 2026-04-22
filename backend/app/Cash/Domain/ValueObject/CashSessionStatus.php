<?php

declare(strict_types=1);

namespace App\Cash\Domain\ValueObject;

final class CashSessionStatus
{
    private const VALID_STATUSES = ['open', 'closing', 'closed', 'abandoned'];

    private function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID_STATUSES, true)) {
            throw new \InvalidArgumentException("Invalid cash session status: {$value}");
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

    public static function closing(): self
    {
        return new self('closing');
    }

    public static function closed(): self
    {
        return new self('closed');
    }

    public static function abandoned(): self
    {
        return new self('abandoned');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isOpen(): bool
    {
        return $this->value === 'open';
    }

    public function isClosing(): bool
    {
        return $this->value === 'closing';
    }

    public function isClosed(): bool
    {
        return $this->value === 'closed';
    }

    public function isAbandoned(): bool
    {
        return $this->value === 'abandoned';
    }

    public function equals(CashSessionStatus $other): bool
    {
        return $this->value === $other->value;
    }
}
