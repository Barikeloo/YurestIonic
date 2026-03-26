<?php

namespace App\User\Application\GetRestaurantUsers;

use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetRestaurantUsers
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(string $restaurantUuid): GetRestaurantUsersResponse
    {
        $users = $this->userRepository->getByRestaurantUuid($restaurantUuid);

        return GetRestaurantUsersResponse::create($users);
    }
}
