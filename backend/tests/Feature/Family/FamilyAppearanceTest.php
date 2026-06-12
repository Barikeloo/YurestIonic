<?php

namespace Tests\Feature\Family;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Mejora 2 — families carry a color and an icon for quick identification in
 * the TPV. Covers persistence and validation of those fields.
 */
final class FamilyAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_a_family_with_color_and_icon(): void
    {
        $tenant = $this->createTenantSession('admin');

        $response = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', [
                'name' => 'Bebidas',
                'color' => '#1a9e5a',
                'icon' => 'coins',
            ])
            ->assertStatus(201)
            ->assertJson(['color' => '#1a9e5a', 'icon' => 'coins']);

        $this->assertDatabaseHas('families', [
            'uuid' => $response->json('id'),
            'color' => '#1a9e5a',
            'icon' => 'coins',
        ]);
    }

    public function test_create_normalises_uppercase_color(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas', 'color' => '#1A9E5A'])
            ->assertStatus(201)
            ->assertJson(['color' => '#1a9e5a']);
    }

    public function test_create_rejects_invalid_color(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas', 'color' => 'green'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    }

    public function test_create_rejects_unknown_icon(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas', 'icon' => 'rocket'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['icon']);
    }

    public function test_creates_a_family_without_appearance(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])
            ->assertStatus(201)
            ->assertJson(['color' => null, 'icon' => null]);
    }

    public function test_updates_family_appearance_and_lists_it(): void
    {
        $tenant = $this->createTenantSession('admin');

        $uuid = $this->withSession($tenant['session'])
            ->postJson('/api/admin/families', ['name' => 'Bebidas'])
            ->json('id');

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/families/{$uuid}", [
                'name' => 'Bebidas',
                'color' => '#d18a1c',
                'icon' => 'wallet',
            ])
            ->assertStatus(200)
            ->assertJson(['color' => '#d18a1c', 'icon' => 'wallet']);

        $list = $this->withSession($tenant['session'])
            ->getJson('/api/admin/families')
            ->assertStatus(200);

        $family = collect($list->json())->firstWhere('id', $uuid);
        $this->assertSame('#d18a1c', $family['color']);
        $this->assertSame('wallet', $family['icon']);
    }
}
