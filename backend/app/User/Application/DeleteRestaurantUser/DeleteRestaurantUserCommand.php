<?php

namespace App\User\Application\DeleteRestaurantUser;

final readonly class DeleteRestaurantUserCommand
{
    public function __construct(
        public string $restaurantUuid,
        public string $userUuid,
    ) {}
}
