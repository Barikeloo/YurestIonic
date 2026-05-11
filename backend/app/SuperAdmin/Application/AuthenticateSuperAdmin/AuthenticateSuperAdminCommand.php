<?php

namespace App\SuperAdmin\Application\AuthenticateSuperAdmin;

final readonly class AuthenticateSuperAdminCommand
{
    public function __construct(
        public string $email,
        public string $plainPassword,
    ) {}
}
