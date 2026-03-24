<?php

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddLineToOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_orders_lines_returns_422_when_invalid_uuids(): void
    {
        $response = $this->postJson('/api/orders/lines', [
            'restaurant_id' => 'not-a-uuid',
            'order_id' => 'not-a-uuid',
            'product_id' => 'not-a-uuid',
            'user_id' => 'not-a-uuid',
            'quantity' => 2,
            'price' => 1000,
            'tax_percentage' => 21,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['restaurant_id', 'order_id', 'product_id', 'user_id']);
    }

    public function test_post_orders_lines_returns_422_when_quantity_is_invalid(): void
    {
        $response = $this->postJson('/api/orders/lines', [
            'restaurant_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'order_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'product_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'user_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'quantity' => 0,
            'price' => 1000,
            'tax_percentage' => 21,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    public function test_post_orders_lines_returns_422_when_required_fields_missing(): void
    {
        $response = $this->postJson('/api/orders/lines', [
            'restaurant_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'product_id', 'user_id', 'quantity', 'price', 'tax_percentage']);
    }
}
