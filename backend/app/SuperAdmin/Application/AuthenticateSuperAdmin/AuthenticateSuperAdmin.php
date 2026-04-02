<?php

namespace App\SuperAdmin\Application\AuthenticateSuperAdmin;

use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use App\User\Domain\Interfaces\PasswordHasherInterface;

final class AuthenticateSuperAdmin
{
    public function __construct(
        private SuperAdminRepositoryInterface $superAdminRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $email, string $plainPassword): AuthenticateSuperAdminResponse
    {
        $superAdmin = $this->superAdminRepository->findByEmail($email);

        if ($superAdmin === null) {
            return AuthenticateSuperAdminResponse::invalidCredentials();
        }

        if (! $this->passwordHasher->verify($plainPassword, $superAdmin->passwordHash())) {
            return AuthenticateSuperAdminResponse::invalidCredentials();
        }

        return AuthenticateSuperAdminResponse::success(
            $superAdmin->id(),
            $superAdmin->name(),
            $superAdmin->email(),
        );
    }
}
