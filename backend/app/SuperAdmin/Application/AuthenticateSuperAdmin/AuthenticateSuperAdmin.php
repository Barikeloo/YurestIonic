<?php

namespace App\SuperAdmin\Application\AuthenticateSuperAdmin;

use App\Shared\Domain\ValueObject\Email;
use App\SuperAdmin\Domain\Exception\InvalidSuperAdminCredentialsException;
use App\SuperAdmin\Domain\Interfaces\SuperAdminRepositoryInterface;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use InvalidArgumentException;

class AuthenticateSuperAdmin
{
    public function __construct(
        private SuperAdminRepositoryInterface $superAdminRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(AuthenticateSuperAdminCommand $command): AuthenticateSuperAdminResponse
    {
        try {
            $superAdmin = $this->superAdminRepository->findByEmail(Email::create($command->email));
        } catch (InvalidArgumentException) {
            throw InvalidSuperAdminCredentialsException::create();
        }

        if ($superAdmin === null) {
            throw InvalidSuperAdminCredentialsException::create();
        }

        if (! $this->passwordHasher->verify($command->plainPassword, $superAdmin->passwordHash()->value())) {
            throw InvalidSuperAdminCredentialsException::create();
        }

        return AuthenticateSuperAdminResponse::create(
            id: $superAdmin->id()->value(),
            name: $superAdmin->name()->value(),
            email: $superAdmin->email()->value(),
        );
    }
}
