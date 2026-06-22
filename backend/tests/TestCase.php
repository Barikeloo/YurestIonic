<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{

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

    protected function createGuestOrderFixture(array $tenant): array
    {
        $taxId = (int) DB::table('taxes')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid'          => (string) Str::uuid(),
            'name'          => 'IVA Test',
            'percentage'    => 10,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $zoneId = (int) DB::table('zones')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid'          => (string) Str::uuid(),
            'name'          => 'Terraza Test',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $tableUuid = (string) Str::uuid();
        $tableId   = (int) DB::table('tables')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'zone_id'       => $zoneId,
            'uuid'          => $tableUuid,
            'name'          => 'Mesa Test',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $qrToken = bin2hex(random_bytes(32));
        DB::table('table_qr_tokens')->insert([
            'uuid'            => (string) Str::uuid(),
            'table_id'        => $tableId,
            'restaurant_id'   => $tenant['restaurant_id'],
            'token'           => $qrToken,
            'catalog_version' => 1,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $familyId = (int) DB::table('families')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid'          => (string) Str::uuid(),
            'name'          => 'Bebidas Test',
            'active'        => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        DB::table('products')->insert([
            'restaurant_id' => $tenant['restaurant_id'],
            'family_id'     => $familyId,
            'tax_id'        => $taxId,
            'uuid'          => (string) Str::uuid(),
            'name'          => 'Agua Test',
            'price'         => 150,
            'stock'         => 100,
            'active'        => true,
            'available'     => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return [
            'token'    => $qrToken,
            'table_id' => $tableId,
            'tax_id'   => $taxId,
        ];
    }

    protected function createCashSessionForTests(array $tenant, string $deviceId = 'test-device-001'): void
    {
        DB::table('cash_sessions')->insert([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'device_id' => $deviceId,
            'opened_by_user_id' => DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id'),
            'opened_at' => now(),
            'closed_at' => null,
            'initial_amount_cents' => 0,
            'final_amount_cents' => null,
            'expected_amount_cents' => null,
            'discrepancy_cents' => null,
            'discrepancy_reason' => null,
            'z_report_number' => null,
            'z_report_hash' => null,
            'notes' => null,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
