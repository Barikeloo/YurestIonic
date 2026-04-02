<?php

namespace App\User\Domain\Interfaces;

interface UserQuickAccessRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function getQuickUsersByDeviceId(string $deviceId): array;
}
