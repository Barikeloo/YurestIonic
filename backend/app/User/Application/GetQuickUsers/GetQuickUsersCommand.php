<?php

namespace App\User\Application\GetQuickUsers;

final readonly class GetQuickUsersCommand
{
    public function __construct(
        public string $deviceId,
        public ?string $restaurantUuid,
    ) {}
}
