<?php

namespace App\User\Application\GetRestaurantUsers;

use App\User\Domain\Interfaces\UserRepositoryInterface;

class GetRestaurantUsers
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function __invoke(GetRestaurantUsersCommand $command): GetRestaurantUsersResponse
    {
        $users = $this->userRepository->getByRestaurantUuid($command->restaurantUuid);

        return new GetRestaurantUsersResponse($users);
    }
}
