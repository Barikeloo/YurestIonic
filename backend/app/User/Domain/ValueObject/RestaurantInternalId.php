<?php

namespace App\User\Domain\ValueObject;

/**
 * Internal integer FK from users.restaurant_id to restaurants.id.
 * Not a UUID — represents the database internal id of the linked restaurant.
 */
final class RestaurantInternalId
{
    private const MIN_VALUE = 1;

    private string $value;

    private function __construct(string $value)
    {
        $numericValue = (int) $value;

        if ($numericValue < self::MIN_VALUE) {
            throw new \InvalidArgumentException(
                sprintf('Restaurant internal id must be a positive integer, got: %s', $value)
            );
        }

        $this->value = $value;
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function fromInt(int $value): self
    {
        return new self((string) $value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function toInt(): int
    {
        return (int) $this->value;
    }
}
