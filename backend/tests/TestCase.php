<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    /**
     * @return array{session: array<string, string>, super_admin_uuid: string}
     */
    protected function createSuperAdminSession(): array
    {
        $superAdminUuid = (string) Str::uuid();

        DB::table('super_admins')->insert([
            'uuid' => $superAdminUuid,
            'name' => 'Platform Superadmin',
            'email' => 'superadmin-'.Str::lower(Str::random(8)).'@local.test',
            'password' => Hash::make('superadmin123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'session' => ['super_admin_id' => $superAdminUuid],
            'super_admin_uuid' => $superAdminUuid,
        ];
    }

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
            'tax_id' => 'B'.random_int(10000000, 99999999),
            'email' => 'restaurant-'.Str::lower(Str::random(8)).'@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userUuid = (string) Str::uuid();
        DB::table('users')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => $userUuid,
            'role' => $role,
            'name' => 'Test User',
            'email' => 'user-'.Str::lower(Str::random(8)).'@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'session' => ['auth_user_id' => $userUuid],
            'restaurant_id' => $restaurantId,
            'restaurant_uuid' => $restaurantUuid,
            'user_uuid' => $userUuid,
        ];
    }
}
