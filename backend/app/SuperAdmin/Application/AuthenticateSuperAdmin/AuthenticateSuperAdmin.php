<?php

namespace App\SuperAdmin\Application\AuthenticateSuperAdmin;

use App\Shared\Domain\ValueObject\Email;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use InvalidArgumentException;

final class AuthenticateSuperAdmin
{
    public function __construct(
        private SuperAdminRepositoryInterface $superAdminRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $email, string $plainPassword): AuthenticateSuperAdminResponse
    {
        try {
            $superAdmin = $this->superAdminRepository->findByEmail(Email::create($email));
        } catch (InvalidArgumentException) {
            return AuthenticateSuperAdminResponse::invalidCredentials();
        }

        if ($superAdmin === null) {
            return AuthenticateSuperAdminResponse::invalidCredentials();
        }

        if (! $this->passwordHasher->verify($plainPassword, $superAdmin->passwordHash()->value())) {
            return AuthenticateSuperAdminResponse::invalidCredentials();
        }

        return AuthenticateSuperAdminResponse::success(
            $superAdmin->id()->value(),
            $superAdmin->name()->value(),
            $superAdmin->email()->value(),
        );
    }
}
