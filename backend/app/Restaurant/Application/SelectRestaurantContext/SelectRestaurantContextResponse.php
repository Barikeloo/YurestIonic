<?php

namespace App\Restaurant\Application\SelectRestaurantContext;

class SelectRestaurantContextResponse
{
    private function __construct(
        private string $restaurantUuid,
        private string $restaurantName,
    ) {}

    public static function create(string $restaurantUuid, string $restaurantName): self
    {
        return new self($restaurantUuid, $restaurantName);
    }

    public function restaurantUuid(): string
    {
        return $this->restaurantUuid;
    }

    public function restaurantName(): string
    {
        return $this->restaurantName;
    }
}
