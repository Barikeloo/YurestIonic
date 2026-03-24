<?php

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_orders_returns_422_when_invalid_uuids(): void
    {
        $response = $this->postJson('/api/orders', [
            'restaurant_id' => 'not-a-uuid',
            'table_id' => 'not-a-uuid',
            'opened_by_user_id' => 'not-a-uuid',
            'diners' => 4,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['restaurant_id', 'table_id', 'opened_by_user_id']);
    }

    public function test_post_orders_returns_422_when_diners_is_invalid(): void
    {
        $response = $this->postJson('/api/orders', [
            'restaurant_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'table_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'opened_by_user_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'diners' => 0,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['diners']);
    }

    public function test_post_orders_returns_422_when_required_fields_missing(): void
    {
        $response = $this->postJson('/api/orders', [
            'restaurant_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['table_id', 'opened_by_user_id', 'diners']);
    }
}
