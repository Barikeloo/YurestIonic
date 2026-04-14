<?php

namespace App\Restaurant\Domain\ValueObject;

final class RestaurantTaxId
{
    private const MAX_LENGTH = 64;

    private function __construct(
        private string $value,
    ) {
        $trimmed = strtoupper(trim($value));

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Restaurant tax id cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Restaurant tax id cannot exceed %d characters.', self::MAX_LENGTH)
            );
        }

        $this->value = $trimmed;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function createNullable(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }
}
