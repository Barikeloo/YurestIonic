<?php

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_orders_returns_422_when_invalid_uuids(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/orders', [
            'restaurant_id' => $tenant['restaurant_uuid'],
            'table_id' => 'not-a-uuid',
            'opened_by_user_id' => 'not-a-uuid',
            'diners' => 4,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['table_id', 'opened_by_user_id']);
    }

    public function test_post_orders_returns_422_when_diners_is_invalid(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/orders', [
            'restaurant_id' => $tenant['restaurant_uuid'],
            'table_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'opened_by_user_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'diners' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['diners']);
    }

    public function test_post_orders_returns_422_when_required_fields_missing(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/orders', [
            'restaurant_id' => $tenant['restaurant_uuid'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['table_id', 'opened_by_user_id', 'diners']);
    }
}
