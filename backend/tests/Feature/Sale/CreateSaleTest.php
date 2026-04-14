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

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/sales', [
            'restaurant_id' => $tenant['restaurant_uuid'],
            'order_id' => 'not-a-uuid',
            'opened_by_user_id' => 'not-a-uuid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'opened_by_user_id']);
    }

    public function test_post_sales_returns_422_when_opened_by_user_is_invalid(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/sales', [
            'restaurant_id' => $tenant['restaurant_uuid'],
            'order_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'opened_by_user_id' => 'invalid-uuid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['opened_by_user_id']);
    }

    public function test_post_sales_returns_422_when_required_fields_missing(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/sales', [
            'restaurant_id' => $tenant['restaurant_uuid'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'opened_by_user_id']);
    }
}
