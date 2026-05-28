<?php

namespace App\User\Application\DeleteRestaurantUser;

final readonly class DeleteRestaurantUserCommand
{
    public function __construct(
        public string $restaurantUuid,
        public string $userUuid,
        public ?string $actorUserUuid = null,
        public ?string $actorSuperAdminUuid = null,
        public ?string $deviceId = null,
        public ?string $ipAddress = null,
    ) {}
}
