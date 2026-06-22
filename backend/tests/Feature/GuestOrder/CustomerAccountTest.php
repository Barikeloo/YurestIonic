<?php

declare(strict_types=1);

namespace Tests\Feature\GuestOrder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_account_and_returns_auth_token(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $response = $this->postJson("/api/public/table/{$fixture['token']}/auth/register", [
            'name'     => 'Ana García',
            'email'    => 'ana@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['customer', 'customer_auth_token'])
            ->assertJsonPath('customer.name', 'Ana García')
            ->assertJsonPath('customer.email', 'ana@test.com')
            ->assertJsonPath('customer.points', 0);

        $this->assertSame(64, strlen($response->json('customer_auth_token')));
    }

    public function test_register_returns_409_on_duplicate_email(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $body = ['name' => 'Test', 'email' => 'dup@test.com', 'password' => 'password123'];
        $this->postJson("/api/public/table/{$fixture['token']}/auth/register", $body)->assertStatus(201);
        $this->postJson("/api/public/table/{$fixture['token']}/auth/register", $body)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'EMAIL_ALREADY_REGISTERED');
    }

    public function test_register_validates_password_minimum_length(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->postJson("/api/public/table/{$fixture['token']}/auth/register", [
            'name'     => 'Test',
            'email'    => 'test@test.com',
            'password' => 'short',
        ])->assertStatus(422);
    }

    public function test_login_returns_200_with_customer_and_token(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->postJson("/api/public/table/{$fixture['token']}/auth/register", [
            'name'     => 'Carlos',
            'email'    => 'carlos@test.com',
            'password' => 'mypassword',
        ]);

        $response = $this->postJson("/api/public/table/{$fixture['token']}/auth/login", [
            'email'    => 'carlos@test.com',
            'password' => 'mypassword',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('customer.name', 'Carlos')
            ->assertJsonPath('customer.email', 'carlos@test.com')
            ->assertJsonStructure(['customer_auth_token']);
    }

    public function test_login_returns_401_for_wrong_password(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->postJson("/api/public/table/{$fixture['token']}/auth/register", [
            'name' => 'Test', 'email' => 'test@test.com', 'password' => 'correctpass',
        ]);

        $this->postJson("/api/public/table/{$fixture['token']}/auth/login", [
            'email' => 'test@test.com', 'password' => 'wrongpass',
        ])->assertStatus(401)->assertJsonPath('error.code', 'INVALID_CREDENTIALS');
    }

    public function test_login_returns_401_for_nonexistent_email(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->postJson("/api/public/table/{$fixture['token']}/auth/login", [
            'email' => 'nobody@test.com', 'password' => 'anypass',
        ])->assertStatus(401);
    }

    public function test_email_is_case_insensitive(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->postJson("/api/public/table/{$fixture['token']}/auth/register", [
            'name' => 'Test', 'email' => 'User@Test.COM', 'password' => 'password123',
        ])->assertStatus(201);

        $this->postJson("/api/public/table/{$fixture['token']}/auth/login", [
            'email' => 'user@test.com', 'password' => 'password123',
        ])->assertStatus(200);
    }
}
