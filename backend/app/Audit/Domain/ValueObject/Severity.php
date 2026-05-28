<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

final class Severity
{
    private const VALID = ['info', 'warning', 'danger', 'critical', 'success'];

    private function __construct(private readonly string $value)
    {
        if (! in_array($value, self::VALID, true)) {
            throw new \InvalidArgumentException("Invalid audit severity: {$value}");
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(Severity $other): bool
    {
        return $this->value === $other->value;
    }
}
