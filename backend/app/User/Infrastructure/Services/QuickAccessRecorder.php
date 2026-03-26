<?php

namespace App\User\Infrastructure\Services;

use App\User\Infrastructure\Persistence\Models\EloquentUser;
use App\User\Infrastructure\Persistence\Models\EloquentUserQuickAccess;

final class QuickAccessRecorder
{
    public function record(string $userUuid, string $deviceId): void
    {
        $user = EloquentUser::query()
            ->select('id', 'restaurant_id')
            ->where('uuid', $userUuid)
            ->first();

        if ($user === null || ! is_numeric($user->restaurant_id)) {
            return;
        }

        EloquentUserQuickAccess::query()->updateOrCreate(
            [
                'restaurant_id' => (int) $user->restaurant_id,
                'user_id' => (int) $user->id,
                'device_id' => $deviceId,
            ],
            [
                'last_login_at' => now(),
                'updated_at' => now(),
            ],
        );
    }
}
