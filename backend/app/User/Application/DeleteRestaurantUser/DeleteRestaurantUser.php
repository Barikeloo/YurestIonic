<?php

namespace App\User\Application\DeleteRestaurantUser;

use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $restaurantUuid, string $uuid): DeleteRestaurantUserResponse
    {
        $user = $this->userRepository->findById($uuid);

        if ($user === null) {
            return DeleteRestaurantUserResponse::notFound();
        }

        // Verify the target user belongs to the same restaurant
        $userRestaurantId = $user->restaurantId();
        if ($userRestaurantId === null || $userRestaurantId->value() !== $restaurantUuid) {
            return DeleteRestaurantUserResponse::notFound();
        }

        $this->userRepository->delete($uuid);

        return DeleteRestaurantUserResponse::success();
    }
}
