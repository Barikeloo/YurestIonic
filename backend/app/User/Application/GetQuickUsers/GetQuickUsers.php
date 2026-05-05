<?php

namespace App\User\Application\GetQuickUsers;

use App\User\Domain\Interfaces\UserQuickAccessRepositoryInterface;

class GetQuickUsers
{
    public function __construct(
        private UserQuickAccessRepositoryInterface $userQuickAccessRepository,
    ) {}

    public function __invoke(GetQuickUsersCommand $command): GetQuickUsersResponse
    {
        $users = $this->userQuickAccessRepository->getQuickUsersByDeviceId($command->deviceId, $command->restaurantUuid);

        return new GetQuickUsersResponse($users);
    }
}
