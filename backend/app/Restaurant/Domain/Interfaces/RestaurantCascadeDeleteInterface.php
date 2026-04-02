<?php

namespace App\Restaurant\Domain\Interfaces;

interface RestaurantCascadeDeleteInterface
{
    public function deleteByRestaurantUuid(string $restaurantUuid): bool;
}
