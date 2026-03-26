<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class LoginUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_200_with_user_when_credentials_are_valid(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Auth User',
            'email' => 'auth@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'auth@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'name' => 'Auth User',
            'email' => 'auth@example.com',
        ]);
        $response->assertJsonStructure([
            'success',
            'id',
            'name',
            'email',
            'role',
            'restaurant_id',
            'restaurant_name',
        ]);
    }

    public function test_login_returns_404_when_user_is_not_registered(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'missing@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'message' => 'User not registered.',
        ]);
    }

    public function test_login_returns_401_when_password_is_invalid(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Auth User',
            'email' => 'auth@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'auth@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid credentials.',
        ]);
    }

    public function test_me_returns_401_when_user_is_not_authenticated(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Not authenticated.',
        ]);
    }

    public function test_me_returns_authenticated_user_after_login(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Session User',
            'email' => 'session@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        $this->postJson('/api/auth/login', [
            'email' => 'session@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'name' => 'Session User',
            'email' => 'session@example.com',
        ]);
        $response->assertJsonStructure([
            'success',
            'id',
            'name',
            'email',
            'role',
            'restaurant_id',
            'restaurant_name',
        ]);
    }

    public function test_logout_invalidates_session_and_me_returns_401(): void
    {
        $this->postJson('/api/users', [
            'name' => 'Session User',
            'email' => 'session@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(201);

        $this->postJson('/api/auth/login', [
            'email' => 'session@example.com',
            'password' => 'password123',
        ])->assertStatus(200);

        $this->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out.',
            ]);

        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Not authenticated.',
            ]);
    }

    public function test_login_pin_returns_200_when_pin_is_valid(): void
    {
        $restaurantUuid = (string) Str::uuid();
        $restaurantId = DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'Pin Restaurant',
            'legal_name' => 'Pin Restaurant S.L.',
            'tax_id' => 'B12312312',
            'email' => 'pin-restaurant@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userUuid = (string) Str::uuid();
        DB::table('users')->insert([
            'restaurant_id' => $restaurantId,
            'uuid' => $userUuid,
            'role' => 'operator',
            'name' => 'Pin User',
            'email' => 'pin-user@local.test',
            'pin' => Hash::make('1234'),
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/auth/login-pin', [
            'user_uuid' => $userUuid,
            'pin' => '1234',
            'device_id' => 'device-test-1',
        ])->assertStatus(200)
            ->assertJson([
                'success' => true,
                'name' => 'Pin User',
                'role' => 'operator',
                'restaurant_name' => 'Pin Restaurant',
            ]);
    }

    public function test_quick_users_returns_last_logins_for_device(): void
    {
        $restaurantUuid = (string) Str::uuid();
        $restaurantId = DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'Quick Access Restaurant',
            'legal_name' => 'Quick Access Restaurant S.L.',
            'tax_id' => 'B99112233',
            'email' => 'quick-restaurant@local.test',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userUuid = (string) Str::uuid();
        $userId = DB::table('users')->insertGetId([
            'restaurant_id' => $restaurantId,
            'uuid' => $userUuid,
            'role' => 'operator',
            'name' => 'Quick User',
            'email' => 'quick-user@local.test',
            'pin' => Hash::make('1234'),
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('user_quick_accesses')->insert([
            'restaurant_id' => $restaurantId,
            'user_id' => $userId,
            'device_id' => 'device-test-2',
            'last_login_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->getJson('/api/auth/quick-users?device_id=device-test-2')
            ->assertStatus(200)
            ->assertJsonPath('users.0.user_uuid', $userUuid)
            ->assertJsonPath('users.0.name', 'Quick User')
            ->assertJsonPath('users.0.restaurant_name', 'Quick Access Restaurant');
    }
}
