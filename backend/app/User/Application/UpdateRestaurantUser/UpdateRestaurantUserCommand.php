<?php

namespace App\User\Application\UpdateRestaurantUser;

final readonly class UpdateRestaurantUserCommand
{
    public function __construct(
        public string $restaurantUuid,
        public string $userUuid,
        public ?string $name = null,
        public ?string $email = null,
        public ?string $plainPassword = null,
        public ?string $role = null,
        public ?string $plainPin = null,
        public ?string $actorUserUuid = null,
    ) {}
}
