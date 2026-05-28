<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

final class ActionSlug
{
    private function __construct(private readonly string $value)
    {
        if (! preg_match('/^[a-z]+\.[a-z_]+$/', $value)) {
            throw new \InvalidArgumentException("Invalid audit action slug: {$value}. Expected format: module.action_name");
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

    public function module(): string
    {
        return explode('.', $this->value)[0];
    }

    public function equals(ActionSlug $other): bool
    {
        return $this->value === $other->value;
    }
}
