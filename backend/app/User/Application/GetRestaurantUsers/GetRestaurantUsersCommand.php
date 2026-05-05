<?php

namespace App\User\Application\GetRestaurantUsers;

final readonly class GetRestaurantUsersCommand
{
    public function __construct(
        public string $restaurantUuid,
    ) {}
}
