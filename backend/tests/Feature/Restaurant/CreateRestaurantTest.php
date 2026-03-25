<?php

namespace Tests\Feature\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRestaurantTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_restaurants_returns_422_when_email_already_exists(): void
    {
        $admin = $this->createTenantSession('admin');

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
        $admin = $this->createTenantSession('admin');

        $response = $this->withSession($admin['session'])->postJson('/api/admin/restaurants', [
            'name' => 'Incomplete Restaurant',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_post_restaurants_returns_422_when_email_is_invalid(): void
    {
        $admin = $this->createTenantSession('admin');

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
}
