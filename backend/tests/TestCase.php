<?php

namespace Tests;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array{session: array<string, string>, restaurant_id: int, restaurant_uuid: string, user_uuid: string}
     */
    protected function createTenantSession(string $role = 'operator'): array
    {
        $restaurantUuid = (string) Str::uuid();
        $restaurantId = (int) DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'Test Restaurant',
            'legal_name' => 'Test Restaurant S.L.',
            'tax_id' => 'B' . random_int(10000000, 99999999),
            'email' => 'restaurant-' . Str::lower(Str::random(8)) . '@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userUuid = (string) Str::uuid();
        DB::table('users')->insert([
            'restaurant_id' => $role === 'admin' ? null : $restaurantId,
            'uuid' => $userUuid,
            'role' => $role,
            'name' => 'Test User',
            'email' => 'user-' . Str::lower(Str::random(8)) . '@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = ['auth_user_id' => $userUuid];

        if ($role === 'admin') {
            $session['tenant_restaurant_uuid'] = $restaurantUuid;
        }

        return [
            'session' => $session,
            'restaurant_id' => $restaurantId,
            'restaurant_uuid' => $restaurantUuid,
            'user_uuid' => $userUuid,
        ];
    }
}
