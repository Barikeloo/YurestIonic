<?php

namespace App\User\Application\AuthenticateUserByPin;

final readonly class AuthenticateUserByPinCommand
{
    public function __construct(
        public string $userUuid,
        public string $pin,
        public ?string $restaurantUuid,
        public ?string $deviceId,
    ) {}
}
