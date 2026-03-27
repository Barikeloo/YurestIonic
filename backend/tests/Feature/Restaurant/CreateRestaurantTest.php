<?php

namespace Tests\Feature\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreateRestaurantTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_restaurants_returns_422_when_email_already_exists(): void
    {
        $admin = $this->createSuperAdminSession();

        $payload = [
            'name' => 'Duplicated Restaurant',
            'email' => 'duplicate@restaurant.com',
            'password' => 'password123',
        ];

        $this->withSession($admin['session'])->postJson('/api/admin/restaurants', $payload)->assertStatus(201);

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_post_restaurants_returns_422_when_required_fields_missing(): void
    {
        $admin = $this->createSuperAdminSession();

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Incomplete Restaurant',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_post_restaurants_returns_422_when_email_is_invalid(): void
    {
        $admin = $this->createSuperAdminSession();

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Test Restaurant',
            'legal_name' => 'Test Restaurant S.L.',
            'tax_id' => 'B12345678',
            'email' => 'invalid-email',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_post_restaurants_generates_admin_pin_and_allows_pin_login(): void
    {
        $admin = $this->createSuperAdminSession();

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Pin Ready Restaurant',
            'legal_name' => 'Pin Ready Restaurant S.L.',
            'tax_id' => 'B77112233',
            'email' => 'pin-ready@restaurant.local',
            'password' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'uuid',
            'name',
            'legal_name',
            'tax_id',
            'email',
            'admin_pin',
        ]);

        $generatedPin = (string) $response->json('admin_pin');
        $this->assertMatchesRegularExpression('/^\d{4}$/', $generatedPin);

        $adminUserUuid = (string) DB::table('users')
            ->where('email', 'pin-ready@restaurant.local')
            ->value('uuid');

        $this->assertNotSame('', $adminUserUuid);

        $this->postJson('/api/auth/login-pin', [
            'user_uuid' => $adminUserUuid,
            'pin' => $generatedPin,
            'device_id' => 'test-device-pin-ready',
        ])->assertStatus(200)
            ->assertJson([
                'success' => true,
                'email' => 'pin-ready@restaurant.local',
                'role' => 'admin',
            ]);
    }
}
