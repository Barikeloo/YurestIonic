<?php

namespace App\User\Application\CreateRestaurantUser;

final readonly class CreateRestaurantUserCommand
{
    public function __construct(
        public string $name,
        public string $email,
        public string $plainPassword,
        public string $restaurantUuid,
        public string $role = 'operator',
        public ?string $plainPin = null,
    ) {}
}
