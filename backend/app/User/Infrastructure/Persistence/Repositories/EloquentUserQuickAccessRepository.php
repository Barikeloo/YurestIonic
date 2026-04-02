<?php

namespace App\User\Infrastructure\Persistence\Repositories;

use App\User\Domain\Interfaces\UserQuickAccessRepositoryInterface;
use App\User\Infrastructure\Persistence\Models\EloquentUserQuickAccess;

final class EloquentUserQuickAccessRepository implements UserQuickAccessRepositoryInterface
{
    public function getQuickUsersByDeviceId(string $deviceId): array
    {
        return EloquentUserQuickAccess::query()
            ->join('users', 'users.id', '=', 'user_quick_accesses.user_id')
            ->join('restaurants', 'restaurants.id', '=', 'user_quick_accesses.restaurant_id')
            ->where('user_quick_accesses.device_id', $deviceId)
            ->whereNull('users.deleted_at')
            ->whereNotNull('users.pin')
            ->where('users.pin', '!=', '')
            ->orderByDesc('user_quick_accesses.last_login_at')
            ->limit(6)
            ->get([
                'users.uuid as user_uuid',
                'users.name',
                'users.role',
                'restaurants.uuid as restaurant_uuid',
                'restaurants.name as restaurant_name',
                'user_quick_accesses.last_login_at',
            ])
            ->map(static fn ($row): array => [
                'user_uuid' => $row->user_uuid,
                'name' => $row->name,
                'role' => $row->role,
                'restaurant_uuid' => $row->restaurant_uuid,
                'restaurant_name' => $row->restaurant_name,
                'last_login_at' => $row->last_login_at,
            ])
            ->all();
    }
}
