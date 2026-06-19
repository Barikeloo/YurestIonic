<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ValueObject;

final class SendStatus
{
    public const PENDING = 'pending';
    public const SENT    = 'sent';

    private const VALID = [self::PENDING, self::SENT];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid send status: {$value}.");
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public static function sent(): self
    {
        return new self(self::SENT);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isPending(): bool
    {
        return $this->value === self::PENDING;
    }

    public function isSent(): bool
    {
        return $this->value === self::SENT;
    }
}
