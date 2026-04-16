<?php

namespace App\User\Domain\ValueObject;

class Role
{
    public const OPERATOR = 'operator';

    public const SUPERVISOR = 'supervisor';

    public const ADMIN = 'admin';

    private const VALID_ROLES = [
        self::OPERATOR,
        self::SUPERVISOR,
        self::ADMIN,
    ];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_ROLES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid role: %s. Valid roles are: %s', $value, implode(', ', self::VALID_ROLES))
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function operator(): self
    {
        return new self(self::OPERATOR);
    }

    public static function supervisor(): self
    {
        return new self(self::SUPERVISOR);
    }

    public static function admin(): self
    {
        return new self(self::ADMIN);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isOperator(): bool
    {
        return $this->value === self::OPERATOR;
    }

    public function isSupervisor(): bool
    {
        return $this->value === self::SUPERVISOR;
    }

    public function isAdmin(): bool
    {
        return $this->value === self::ADMIN;
    }
}
