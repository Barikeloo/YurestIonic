<?php

namespace App\User\Application\AuthenticateUser;

use App\Restaurant\Domain\Interfaces\RestaurantRepositoryInterface;
use App\Shared\Domain\ValueObject\Email;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class AuthenticateUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private RestaurantRepositoryInterface $restaurantRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(string $email, string $plainPassword): AuthenticateUserResponse
    {
        $emailVO = Email::create($email);
        $user = $this->userRepository->findByEmail($emailVO->value());

        if ($user === null) {
            return AuthenticateUserResponse::notFound();
        }

        $isValidPassword = $this->passwordHasher->verify($plainPassword, $user->passwordHash()->value());

        if (! $isValidPassword) {
            return AuthenticateUserResponse::invalidCredentials();
        }

        $role = $user->role();
        $restaurantId = null;
        $restaurantName = null;

        if (is_numeric($user->restaurantId())) {
            $restaurant = $this->restaurantRepository->findByInternalId((int) $user->restaurantId());

            if ($restaurant !== null) {
                $restaurantId = $restaurant->getUuid()->value();
                $restaurantName = $restaurant->getName();
            }
        }

        return AuthenticateUserResponse::authenticated(
            $user,
            $role,
            $restaurantId,
            $restaurantName,
        );
    }
}

