<?php

namespace App\User\Application\AuthenticateForDeviceLink;

final readonly class AuthenticateForDeviceLinkCommand
{
    public function __construct(
        public string $email,
        public string $password,
        public ?string $deviceId,
    ) {}
}
