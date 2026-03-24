<?php

namespace App\User\Application\AuthenticateUser;

use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class AuthenticateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $email, string $plainPassword): AuthenticateUserResponse
    {
        $emailVO = Email::create($email);
        $user = $this->userRepository->findByEmail($emailVO->value());

        if ($user === null) {
            return AuthenticateUserResponse::notFound();
        }

        $isValidPassword = $this->passwordHasher->verify($plainPassword, $user->passwordHash());

        if (! $isValidPassword) {
            return AuthenticateUserResponse::invalidCredentials();
        }

        return AuthenticateUserResponse::authenticated($user);
    }
}
