<?php

namespace Tests\Feature\Sale;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddLineToSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_sales_lines_returns_422_when_invalid_uuids(): void
    {
        $response = $this->postJson('/api/sales/lines', [
            'restaurant_id' => 'not-a-uuid',
            'sale_id' => 'not-a-uuid',
            'order_line_id' => 'not-a-uuid',
            'user_id' => 'not-a-uuid',
            'quantity' => 2,
            'price' => 1000,
            'tax_percentage' => 21,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['restaurant_id', 'sale_id', 'order_line_id', 'user_id']);
    }

    public function test_post_sales_lines_returns_422_when_quantity_is_invalid(): void
    {
        $response = $this->postJson('/api/sales/lines', [
            'restaurant_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'sale_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'order_line_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'user_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'quantity' => -1,
            'price' => 1000,
            'tax_percentage' => 21,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['quantity']);
    }

    public function test_post_sales_lines_returns_422_when_required_fields_missing(): void
    {
        $response = $this->postJson('/api/sales/lines', [
            'restaurant_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sale_id', 'order_line_id', 'user_id', 'quantity', 'price', 'tax_percentage']);
    }
}
