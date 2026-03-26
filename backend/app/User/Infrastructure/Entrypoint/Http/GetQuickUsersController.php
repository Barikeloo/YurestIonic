<?php

namespace App\User\Infrastructure\Entrypoint\Http;

use App\User\Infrastructure\Persistence\Models\EloquentUserQuickAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetQuickUsersController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:100'],
        ]);

        $quickUsers = EloquentUserQuickAccess::query()
            ->join('users', 'users.id', '=', 'user_quick_accesses.user_id')
            ->join('restaurants', 'restaurants.id', '=', 'user_quick_accesses.restaurant_id')
            ->where('user_quick_accesses.device_id', $validated['device_id'])
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

        return new JsonResponse([
            'users' => $quickUsers,
        ]);
    }
}
