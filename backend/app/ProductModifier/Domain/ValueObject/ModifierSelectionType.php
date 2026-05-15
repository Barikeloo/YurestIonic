<?php

namespace App\ProductModifier\Domain\ValueObject;

class ModifierSelectionType
{
    private const VALID_TYPES = ['single', 'multi'];

    private string $value;

    private function __construct(string $value)
    {
        if (! in_array($value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid selection type: %s. Allowed: %s.', $value, implode(', ', self::VALID_TYPES))
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function single(): self
    {
        return new self('single');
    }

    public static function multi(): self
    {
        return new self('multi');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isSingle(): bool
    {
        return $this->value === 'single';
    }

    public function isMulti(): bool
    {
        return $this->value === 'multi';
    }
}
