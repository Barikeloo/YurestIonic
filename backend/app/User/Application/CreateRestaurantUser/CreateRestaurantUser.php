<?php

namespace App\User\Application\CreateRestaurantUser;

use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Exception\PinAlreadyInUseException;
use App\User\Domain\Interfaces\PasswordHasherInterface;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class CreateRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(CreateRestaurantUserCommand $command): CreateRestaurantUserResponse
    {
        $userUuid = Uuid::generate()->value();
        $passwordHash = $this->passwordHasher->hash($command->plainPassword);
        $pinHash = is_string($command->plainPin) && $command->plainPin !== ''
            ? $this->passwordHasher->hash($command->plainPin)
            : null;

        if ($pinHash !== null && $this->userRepository->pinHashExistsForRestaurant($pinHash, $command->restaurantUuid)) {
            throw new PinAlreadyInUseException();
        }

        $this->userRepository->saveWithRestaurant(
            $userUuid,
            $command->name,
            $command->email,
            $passwordHash,
            $command->restaurantUuid,
            $command->role,
            $pinHash,
        );

        return new CreateRestaurantUserResponse(
            uuid: $userUuid,
            name: $command->name,
            email: $command->email,
            role: $command->role,
        );
    }
}
