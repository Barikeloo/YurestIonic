<?php

namespace App\User\Application\CreateRestaurantUser;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class CreateRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(
        string $name,
        string $email,
        string $plainPassword,
        string $restaurantUuid,
    ): CreateRestaurantUserResponse {
        $userUuid = Uuid::generate()->value();
        $passwordHash = $this->passwordHasher->hash($plainPassword);

        $this->userRepository->saveWithRestaurant(
            $userUuid,
            $name,
            $email,
            $passwordHash,
            $restaurantUuid,
        );

        return CreateRestaurantUserResponse::create($userUuid, $name, $email);
    }
}

