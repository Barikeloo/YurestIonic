<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\ValueObject;

final class IdentityMode
{
    public const ANONYMOUS  = 'anonymous';
    public const NAMED      = 'named';
    public const REGISTERED = 'registered';

    private const VALID = [self::ANONYMOUS, self::NAMED, self::REGISTERED];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid identity mode: {$value}.");
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function anonymous(): self
    {
        return new self(self::ANONYMOUS);
    }

    public static function named(): self
    {
        return new self(self::NAMED);
    }

    public static function registered(): self
    {
        return new self(self::REGISTERED);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isAnonymous(): bool
    {
        return $this->value === self::ANONYMOUS;
    }

    public function isNamed(): bool
    {
        return $this->value === self::NAMED;
    }

    public function isRegistered(): bool
    {
        return $this->value === self::REGISTERED;
    }
}
