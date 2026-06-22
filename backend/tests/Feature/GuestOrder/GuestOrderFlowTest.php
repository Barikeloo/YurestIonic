<?php

declare(strict_types=1);

namespace Tests\Feature\GuestOrder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class GuestOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_table_status_returns_none_for_empty_table(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->getJson("/api/public/table/{$fixture['token']}")
            ->assertStatus(200)
            ->assertJsonStructure(['restaurant', 'table', 'order_status', 'active_sessions_count'])
            ->assertJson(['order_status' => 'none', 'active_sessions_count' => 0]);
    }

    public function test_get_table_status_returns_404_for_unknown_token(): void
    {
        $this->getJson('/api/public/table/' . str_repeat('a', 64))
            ->assertStatus(404);
    }

    public function test_open_table_creates_session_and_order(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));

        $response = $this->postJson("/api/public/table/{$fixture['token']}/open", [
            'session_token' => $sessionToken,
            'diners_count'  => 3,
            'identity_mode' => 'named',
            'guest_name'    => 'Carlos',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['session_id', 'session_token', 'order_id', 'identity_mode', 'guest_name', 'expires_at'])
            ->assertJson(['identity_mode' => 'named', 'guest_name' => 'Carlos', 'diners_count' => 3]);
    }

    public function test_table_status_shows_open_after_guest_opens_it(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));

        $this->postJson("/api/public/table/{$fixture['token']}/open", [
            'session_token' => $sessionToken,
            'diners_count'  => 2,
            'identity_mode' => 'anonymous',
        ])->assertStatus(201);

        $this->getJson("/api/public/table/{$fixture['token']}")
            ->assertStatus(200)
            ->assertJson(['order_status' => 'open', 'active_sessions_count' => 1]);
    }

    public function test_second_open_returns_409_table_already_open(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->postJson("/api/public/table/{$fixture['token']}/open", [
            'session_token' => bin2hex(random_bytes(32)),
            'diners_count'  => 2,
            'identity_mode' => 'anonymous',
        ])->assertStatus(201);

        $this->postJson("/api/public/table/{$fixture['token']}/open", [
            'session_token' => bin2hex(random_bytes(32)),
            'diners_count'  => 1,
            'identity_mode' => 'anonymous',
        ])->assertStatus(409)->assertJsonPath('error.code', 'TABLE_ALREADY_OPEN');
    }

    public function test_join_session_succeeds_on_open_table(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->postJson("/api/public/table/{$fixture['token']}/open", [
            'session_token' => bin2hex(random_bytes(32)),
            'diners_count'  => 2,
            'identity_mode' => 'anonymous',
        ])->assertStatus(201);

        $this->postJson("/api/public/table/{$fixture['token']}/session", [
            'session_token' => bin2hex(random_bytes(32)),
            'identity_mode' => 'named',
            'guest_name'    => 'María',
        ])->assertStatus(201)->assertJson(['guest_name' => 'María']);
    }

    public function test_join_session_returns_409_on_empty_table(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->postJson("/api/public/table/{$fixture['token']}/session", [
            'session_token' => bin2hex(random_bytes(32)),
            'identity_mode' => 'anonymous',
        ])->assertStatus(409)->assertJsonPath('error.code', 'TABLE_NOT_OPEN');
    }

    public function test_validate_session_returns_valid_true_for_active_session(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));

        $this->postJson("/api/public/table/{$fixture['token']}/open", [
            'session_token' => $sessionToken,
            'diners_count'  => 2,
            'identity_mode' => 'named',
            'guest_name'    => 'Test',
        ])->assertStatus(201);

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->getJson("/api/public/table/{$fixture['token']}/session/validate")
            ->assertStatus(200)
            ->assertJson(['valid' => true, 'order_status' => 'open']);
    }

    public function test_validate_session_returns_valid_false_for_unknown_token(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->withHeaders(['X-Guest-Session' => bin2hex(random_bytes(32))])
            ->getJson("/api/public/table/{$fixture['token']}/session/validate")
            ->assertStatus(200)
            ->assertJson(['valid' => false]);
    }

    public function test_catalog_returns_families_and_version(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->getJson("/api/public/table/{$fixture['token']}/catalog")
            ->assertStatus(200)
            ->assertJsonStructure(['version', 'families', 'menus'])
            ->assertJsonCount(1, 'families');
    }

    public function test_catalog_version_returns_integer(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->getJson("/api/public/table/{$fixture['token']}/catalog/version")
            ->assertStatus(200)
            ->assertJsonStructure(['version']);
    }
}
