<?php

namespace Tests\Feature\SuperAdmin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class DeveloperDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_login_with_email_and_password(): void
    {
        $superAdminUuid = (string) Str::uuid();
        $password = 'developer123';

        DB::table('super_admins')->insert([
            'uuid' => $superAdminUuid,
            'name' => 'Test Developer',
            'email' => 'dev@tpv.local',
            'password' => Hash::make($password),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/superadmin/login', [
            'email' => 'dev@tpv.local',
            'password' => $password,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'id' => $superAdminUuid,
                'name' => 'Test Developer',
                'email' => 'dev@tpv.local',
            ]);
    }

    public function test_superadmin_cannot_login_with_wrong_password(): void
    {
        $superAdminUuid = (string) Str::uuid();

        DB::table('super_admins')->insert([
            'uuid' => $superAdminUuid,
            'name' => 'Test Developer',
            'email' => 'dev@tpv.local',
            'password' => Hash::make('correct123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->postJson('/api/superadmin/login', [
            'email' => 'dev@tpv.local',
            'password' => 'wrong123',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_superadmin_can_list_all_restaurants(): void
    {
        $session = $this->createSuperAdminSession();

        $restaurantUuid1 = (string) Str::uuid();
        $restaurantUuid2 = (string) Str::uuid();

        DB::table('restaurants')->insert([
            ['uuid' => $restaurantUuid1, 'name' => 'Restaurant A', 'legal_name' => 'Restaurant A S.L.', 'tax_id' => 'A12345678', 'email' => 'rest-a@local', 'password' => Hash::make('pass'), 'created_at' => now(), 'updated_at' => now()],
            ['uuid' => $restaurantUuid2, 'name' => 'Restaurant B', 'legal_name' => 'Restaurant B S.L.', 'tax_id' => 'B87654321', 'email' => 'rest-b@local', 'password' => Hash::make('pass'), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->withSession($session['session'])->getJson('/api/admin/restaurants');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.uuid', $restaurantUuid1)
            ->assertJsonPath('data.1.uuid', $restaurantUuid2);
    }

    public function test_superadmin_can_logout(): void
    {
        $session = $this->createSuperAdminSession();

        $response = $this->withSession($session['session'])->postJson('/api/superadmin/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_superadmin_can_select_restaurant_context(): void
    {
        $session = $this->createSuperAdminSession();
        $restaurantUuid = (string) Str::uuid();

        DB::table('restaurants')->insert([
            'uuid' => $restaurantUuid,
            'name' => 'Test Restaurant',
            'legal_name' => 'Test Restaurant S.L.',
            'tax_id' => 'T12345678',
            'email' => 'test@local',
            'password' => Hash::make('pass'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession($session['session'])->postJson('/api/admin/context/restaurant', [
            'restaurant_id' => $restaurantUuid,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'restaurant_id' => $restaurantUuid,
                'name' => 'Test Restaurant',
            ]);
    }

    public function test_superadmin_dashboard_lists_restaurants_grouped_by_tax_id(): void
    {
        $session = $this->createSuperAdminSession();

        // Create restaurants with same tax_id
        DB::table('restaurants')->insert([
            ['uuid' => (string) Str::uuid(), 'name' => 'Restaurant A', 'legal_name' => 'Restaurant A S.L.', 'tax_id' => 'SHARED123', 'email' => 'rest-a@local', 'password' => Hash::make('pass'), 'created_at' => now(), 'updated_at' => now()],
            ['uuid' => (string) Str::uuid(), 'name' => 'Restaurant B', 'legal_name' => 'Restaurant B S.L.', 'tax_id' => 'SHARED123', 'email' => 'rest-b@local', 'password' => Hash::make('pass'), 'created_at' => now(), 'updated_at' => now()],
            ['uuid' => (string) Str::uuid(), 'name' => 'Restaurant C', 'legal_name' => 'Restaurant C S.L.', 'tax_id' => 'OTHER456', 'email' => 'rest-c@local', 'password' => Hash::make('pass'), 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->withSession($session['session'])->getJson('/api/admin/restaurants');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(3, $data);
        $this->assertEquals('SHARED123', $data[0]['tax_id']);
        $this->assertEquals('SHARED123', $data[1]['tax_id']);
        $this->assertEquals('OTHER456', $data[2]['tax_id']);
    }
}

