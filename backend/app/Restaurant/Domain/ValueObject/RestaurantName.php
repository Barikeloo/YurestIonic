<?php

namespace App\Restaurant\Domain\ValueObject;

final class RestaurantName
{
    private const MAX_LENGTH = 255;

    private function __construct(
        private string $value,
    ) {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw new \InvalidArgumentException('Restaurant name cannot be empty.');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Restaurant name cannot exceed %d characters.', self::MAX_LENGTH)
            );
        }

        $this->value = $trimmed;
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
