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
            'tax_id' => 'B55500001',
            'email' => 'duplicate@restaurant.com',
            'password' => 'password123',
            'company_mode' => 'new',
        ];

        $this->withSession($admin['session'])->postJson('/api/admin/restaurants', $payload)->assertStatus(201);

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            ...$payload,
            'tax_id' => 'B55500002',
        ]);

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
        $response->assertJsonValidationErrors(['tax_id', 'email', 'password']);
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

    public function test_post_restaurants_uses_provided_admin_pin(): void
    {
        $admin = $this->createSuperAdminSession();

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Provided Pin Restaurant',
            'legal_name' => 'Provided Pin Restaurant S.L.',
            'tax_id' => 'B99112233',
            'email' => 'provided-pin@restaurant.local',
            'password' => 'password123',
            'pin' => '4321',
            'company_mode' => 'new',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('admin_pin', '4321');

        $adminUserUuid = (string) DB::table('users')
            ->where('email', 'provided-pin@restaurant.local')
            ->value('uuid');

        $this->postJson('/api/auth/login-pin', [
            'user_uuid' => $adminUserUuid,
            'pin' => '4321',
            'device_id' => 'test-device-provided-pin',
        ])->assertStatus(200)
            ->assertJson([
                'success' => true,
                'email' => 'provided-pin@restaurant.local',
            ]);
    }

    public function test_post_restaurants_returns_422_when_new_company_uses_existing_tax_id(): void
    {
        $admin = $this->createSuperAdminSession();

        $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Original Company',
            'legal_name' => 'Original Company S.L.',
            'tax_id' => 'B00110011',
            'email' => 'original-company@restaurant.local',
            'password' => 'password123',
            'company_mode' => 'new',
        ])->assertStatus(201);

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Duplicate Company',
            'legal_name' => 'Duplicate Company S.L.',
            'tax_id' => 'B00110011',
            'email' => 'duplicate-company@restaurant.local',
            'password' => 'password123',
            'company_mode' => 'new',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The tax_id already exists. Use the existing company action to add a branch.',
        ]);
    }

    public function test_post_restaurants_returns_422_when_existing_company_tax_id_does_not_exist(): void
    {
        $admin = $this->createSuperAdminSession();

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Missing Company Branch',
            'legal_name' => 'Missing Company Branch S.L.',
            'tax_id' => 'B00990099',
            'email' => 'missing-branch@restaurant.local',
            'password' => 'password123',
            'company_mode' => 'existing',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'The tax_id does not exist yet. Use New Company to create the first restaurant.',
        ]);
    }

    public function test_post_restaurants_allows_existing_company_branch_when_tax_id_exists(): void
    {
        $admin = $this->createSuperAdminSession();

        $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Main Branch',
            'legal_name' => 'Main Branch S.L.',
            'tax_id' => 'B11223344',
            'email' => 'main-branch@restaurant.local',
            'password' => 'password123',
            'company_mode' => 'new',
        ])->assertStatus(201);

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Second Branch',
            'legal_name' => 'Second Branch S.L.',
            'tax_id' => 'B11223344',
            'email' => 'second-branch@restaurant.local',
            'password' => 'password123',
            'company_mode' => 'existing',
            'pin' => '6789',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('tax_id', 'B11223344');
        $response->assertJsonPath('admin_pin', '6789');
    }
}
