<?php

namespace App\ProductModifier\Domain\ValueObject;

class ModifierType
{
    private const VALID_TYPES = ['extra', 'accompaniment'];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid modifier type: %s. Allowed: %s.', $value, implode(', ', self::VALID_TYPES))
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function extra(): self
    {
        return new self('extra');
    }

    public static function accompaniment(): self
    {
        return new self('accompaniment');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isExtra(): bool
    {
        return $this->value === 'extra';
    }

    public function isAccompaniment(): bool
    {
        return $this->value === 'accompaniment';
    }
}
