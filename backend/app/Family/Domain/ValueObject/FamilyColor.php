<?php

namespace App\Family\Domain\ValueObject;

class FamilyColor
{
    private string $value;

    private function __construct(string $value)
    {
        $normalized = strtolower(trim($value));

        if (! preg_match('/^#[0-9a-f]{6}$/', $normalized)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid family color: %s. Expected a hex color like #1a9e5a.', $value)
            );
        }

        $this->value = $normalized;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
