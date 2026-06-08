<?php

namespace App\User\Domain\Interfaces;

interface UserQuickAccessRepositoryInterface
{

    public function getQuickUsersByDeviceId(string $deviceId, ?string $restaurantUuid = null): array;

    public function recordAccess(string $userUuid, string $deviceId): void;
}
