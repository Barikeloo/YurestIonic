<?php

declare(strict_types=1);

namespace App\Shared\Domain\ValueObject;

final class MovementReasonCode
{
    private const VALID_CODES = [
        'change_refill',
        'supplier_payment',
        'tip_declared',
        'sangria',
        'adjustment',
        'other',
    ];

    private function __construct(private readonly string $value)
    {
        if (!in_array($value, self::VALID_CODES, true)) {
            throw new \InvalidArgumentException("Invalid movement reason code: {$value}");
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function changeRefill(): self
    {
        return new self('change_refill');
    }

    public static function supplierPayment(): self
    {
        return new self('supplier_payment');
    }

    public static function tipDeclared(): self
    {
        return new self('tip_declared');
    }

    public static function sangria(): self
    {
        return new self('sangria');
    }

    public static function adjustment(): self
    {
        return new self('adjustment');
    }

    public static function other(): self
    {
        return new self('other');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(MovementReasonCode $other): bool
    {
        return $this->value === $other->value;
    }
}
