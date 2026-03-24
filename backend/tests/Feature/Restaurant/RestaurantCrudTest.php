<?php

namespace Tests\Feature\Restaurant;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RestaurantCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_restaurant_full_crud_flow(): void
    {
        $createResponse = $this->postJson('/api/restaurants', [
            'name' => 'Nuevo Restaurante',
            'legal_name' => 'Nuevo Restaurante S.L.',
            'tax_id' => 'B11223344',
            'email' => 'nuevo@restaurant.local',
            'password' => 'password123',
        ]);

        $createResponse->assertStatus(201);
        $createResponse->assertJsonFragment([
            'name' => 'Nuevo Restaurante',
            'email' => 'nuevo@restaurant.local',
        ]);

        $restaurantId = $createResponse->json('id');

        $this->getJson('/api/restaurants')
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $restaurantId,
                'name' => 'Nuevo Restaurante',
            ]);

        $this->getJson("/api/restaurants/{$restaurantId}")
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $restaurantId,
                'name' => 'Nuevo Restaurante',
            ]);

        $this->putJson("/api/restaurants/{$restaurantId}", [
            'name' => 'Restaurante Actualizado',
            'legal_name' => 'Restaurante Actualizado S.L.',
        ])
            ->assertStatus(200)
            ->assertJsonFragment([
                'id' => $restaurantId,
                'name' => 'Restaurante Actualizado',
            ]);

        $this->deleteJson("/api/restaurants/{$restaurantId}")
            ->assertStatus(204);

        $this->getJson("/api/restaurants/{$restaurantId}")
            ->assertStatus(404);
    }
}
