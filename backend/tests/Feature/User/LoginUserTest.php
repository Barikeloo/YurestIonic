<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
