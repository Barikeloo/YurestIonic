<?php

namespace App\User\Application\GetQuickUsers;

use App\User\Domain\Interfaces\UserQuickAccessRepositoryInterface;

class GetQuickUsers
{
    public function __construct(
        private UserQuickAccessRepositoryInterface $userQuickAccessRepository,
    ) {}

    public function __invoke(string $deviceId): GetQuickUsersResponse
    {
        $users = $this->userQuickAccessRepository->getQuickUsersByDeviceId($deviceId);
        return GetQuickUsersResponse::create($users);
    }
}
