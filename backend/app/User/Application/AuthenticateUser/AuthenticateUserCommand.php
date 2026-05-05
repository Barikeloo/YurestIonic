<?php

namespace App\User\Application\AuthenticateUser;

final readonly class AuthenticateUserCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
        public ?string $deviceId,
    ) {}
}
