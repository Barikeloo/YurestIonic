<?php

namespace Tests\Feature\Sale;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_sales_returns_422_when_invalid_uuids(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/sales', [
            'restaurant_id' => $tenant['restaurant_uuid'],
            'order_id' => 'not-a-uuid',
            'user_id' => 'not-a-uuid',
            'total' => 2000,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'user_id']);
    }

    public function test_post_sales_returns_422_when_total_is_negative(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/sales', [
            'restaurant_id' => $tenant['restaurant_uuid'],
            'order_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'user_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'total' => -100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['total']);
    }

    public function test_post_sales_returns_422_when_required_fields_missing(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/sales', [
            'restaurant_id' => $tenant['restaurant_uuid'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'user_id', 'total']);
    }
}
