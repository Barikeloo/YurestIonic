<?php

namespace App\Family\Domain\ValueObject;

class FamilyIcon
{
    private string $value;

    private function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new \InvalidArgumentException('Family icon cannot be empty.');
        }

        if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $normalized)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid family icon format: "%s". Must be a valid Lucide icon slug (lowercase, hyphens only).', $value)
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
