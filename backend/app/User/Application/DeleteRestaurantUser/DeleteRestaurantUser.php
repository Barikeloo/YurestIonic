<?php

namespace App\User\Application\DeleteRestaurantUser;

use App\User\Domain\Interfaces\UserRepositoryInterface;

class DeleteRestaurantUser
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $uuid): DeleteRestaurantUserResponse
    {
        $user = $this->userRepository->findById($uuid);

        if ($user === null) {
            return DeleteRestaurantUserResponse::notFound();
        }

        $this->userRepository->delete($uuid);

        return DeleteRestaurantUserResponse::success();
    }
}
