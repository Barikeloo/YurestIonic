<?php

namespace App\User\Application\DeleteRestaurantUser;

use App\User\Domain\Exception\UserNotFoundException;
use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(DeleteRestaurantUserCommand $command): DeleteRestaurantUserResponse
    {
        $user = $this->userRepository->findById($command->userUuid);

        if ($user === null) {
            throw UserNotFoundException::withId($command->userUuid);
        }

        $userRestaurantId = $user->restaurantId();
        if ($userRestaurantId === null || $userRestaurantId->value() !== $command->restaurantUuid) {
            throw UserNotFoundException::withId($command->userUuid);
        }

        $this->userRepository->delete($command->userUuid);

        return new DeleteRestaurantUserResponse($command->userUuid);
    }
}
