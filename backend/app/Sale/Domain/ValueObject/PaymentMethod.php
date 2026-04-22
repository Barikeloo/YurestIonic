<?php

declare(strict_types=1);

namespace App\Sale\Domain\ValueObject;

final class PaymentMethod
{
    private const VALID_METHODS = ['cash', 'card', 'bizum', 'voucher', 'invitation', 'other'];

    private function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID_METHODS, true)) {
            throw new \InvalidArgumentException("Invalid payment method: {$value}");
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function cash(): self
    {
        return new self('cash');
    }

    public static function card(): self
    {
        return new self('card');
    }

    public static function bizum(): self
    {
        return new self('bizum');
    }

    public static function voucher(): self
    {
        return new self('voucher');
    }

    public static function invitation(): self
    {
        return new self('invitation');
    }

    public static function other(): self
    {
        return new self('other');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isCash(): bool
    {
        return $this->value === 'cash';
    }

    public function isCard(): bool
    {
        return $this->value === 'card';
    }

    public function equals(PaymentMethod $other): bool
    {
        return $this->value === $other->value;
    }
}
