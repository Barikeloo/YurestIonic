<?php

namespace Tests\Feature\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_users_returns_201_and_user_json(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Integration User',
            'email' => 'integration@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'id',
            'name',
            'email',
            'created_at',
            'updated_at',
        ]);
        $response->assertJson([
            'name' => 'Integration User',
            'email' => 'integration@example.com',
        ]);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $response->json('id')
        );
    }

    public function test_post_users_returns_422_when_email_already_exists(): void
    {
        $payload = [
            'name' => 'Duplicated User',
            'email' => 'duplicate@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->postJson('/api/users', $payload)->assertStatus(201);

        $response = $this->postJson('/api/users', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
        $this->assertSame('Email is already registered.', $response->json('errors.email.0'));
    }
}
