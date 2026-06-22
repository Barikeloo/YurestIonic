<?php

declare(strict_types=1);

namespace Tests\Feature\GuestOrder;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestOrderCartTest extends TestCase
{
    use RefreshDatabase;

    private function openTable(array $fixture, string $sessionToken): void
    {
        $this->postJson("/api/public/table/{$fixture['token']}/open", [
            'session_token' => $sessionToken,
            'diners_count'  => 2,
            'identity_mode' => 'anonymous',
        ])->assertStatus(201);
    }

    private function getProductUuid(array $tenant): string
    {
        return (string) \Illuminate\Support\Facades\DB::table('products')
            ->where('restaurant_id', $tenant['restaurant_id'])
            ->value('uuid');
    }

    public function test_save_pending_lines_returns_line_ids(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));
        $this->openTable($fixture, $sessionToken);

        $productUuid = $this->getProductUuid($tenant);

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/save", [
                'lines' => [['product_id' => $productUuid, 'quantity' => 2]],
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['line_ids'])
            ->assertJsonCount(1, 'line_ids');
    }

    public function test_get_cart_returns_pending_lines(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));
        $this->openTable($fixture, $sessionToken);

        $productUuid = $this->getProductUuid($tenant);
        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/save", [
                'lines' => [['product_id' => $productUuid, 'quantity' => 1]],
            ]);

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->getJson("/api/public/table/{$fixture['token']}/cart")
            ->assertStatus(200)
            ->assertJsonCount(1, 'lines')
            ->assertJsonPath('lines.0.send_status', 'pending');
    }

    public function test_submit_round_marks_lines_as_sent(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));
        $this->openTable($fixture, $sessionToken);

        $productUuid = $this->getProductUuid($tenant);
        $saveRes     = $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/save", [
                'lines' => [['product_id' => $productUuid, 'quantity' => 1]],
            ])
            ->assertStatus(201);

        $lineId = $saveRes->json('line_ids.0');

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/submit-round", [
                'line_ids'        => [$lineId],
                'idempotency_key' => (string) \Ramsey\Uuid\Uuid::uuid4(),
                'round_label'     => 'Bebidas',
            ])
            ->assertStatus(201)
            ->assertJson(['round_number' => 1, 'label' => 'Bebidas', 'line_count' => 1]);

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->getJson("/api/public/table/{$fixture['token']}/cart")
            ->assertStatus(200)
            ->assertJsonCount(0, 'lines');
    }

    public function test_submit_round_with_same_idempotency_key_returns_200_already_submitted(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));
        $this->openTable($fixture, $sessionToken);

        $productUuid    = $this->getProductUuid($tenant);
        $idempotencyKey = (string) \Ramsey\Uuid\Uuid::uuid4();

        $saveRes = $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/save", [
                'lines' => [['product_id' => $productUuid, 'quantity' => 1]],
            ]);
        $lineId  = $saveRes->json('line_ids.0');

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/submit-round", [
                'line_ids'        => [$lineId],
                'idempotency_key' => $idempotencyKey,
            ])->assertStatus(201);

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/submit-round", [
                'line_ids'        => [$lineId],
                'idempotency_key' => $idempotencyKey,
            ])->assertStatus(200)->assertJson(['already_submitted' => true]);
    }

    public function test_delete_pending_line_removes_it_from_cart(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));
        $this->openTable($fixture, $sessionToken);

        $productUuid = $this->getProductUuid($tenant);
        $saveRes     = $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/save", [
                'lines' => [['product_id' => $productUuid, 'quantity' => 1]],
            ]);
        $lineId = $saveRes->json('line_ids.0');

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->deleteJson("/api/public/table/{$fixture['token']}/cart/line/{$lineId}")
            ->assertStatus(200);

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->getJson("/api/public/table/{$fixture['token']}/cart")
            ->assertJsonCount(0, 'lines');
    }

    public function test_request_check_returns_requested_at(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));
        $this->openTable($fixture, $sessionToken);

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/request-check")
            ->assertStatus(200)
            ->assertJsonStructure(['requested_at']);
    }

    public function test_order_history_shows_rounds_and_pending(): void
    {
        $tenant       = $this->createTenantSession();
        $fixture      = $this->createGuestOrderFixture($tenant);
        $sessionToken = bin2hex(random_bytes(32));
        $this->openTable($fixture, $sessionToken);

        $productUuid = $this->getProductUuid($tenant);
        $saveRes     = $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/save", [
                'lines' => [
                    ['product_id' => $productUuid, 'quantity' => 1],
                    ['product_id' => $productUuid, 'quantity' => 2],
                ],
            ]);
        $lineIds = $saveRes->json('line_ids');

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->postJson("/api/public/table/{$fixture['token']}/cart/submit-round", [
                'line_ids'        => [$lineIds[0]],
                'idempotency_key' => (string) \Ramsey\Uuid\Uuid::uuid4(),
            ]);

        $this->withHeaders(['X-Guest-Session' => $sessionToken])
            ->getJson("/api/public/table/{$fixture['token']}/my-orders")
            ->assertStatus(200)
            ->assertJsonCount(1, 'rounds')
            ->assertJsonCount(1, 'pending_lines');
    }

    public function test_cart_returns_401_without_session_header(): void
    {
        $tenant  = $this->createTenantSession();
        $fixture = $this->createGuestOrderFixture($tenant);

        $this->getJson("/api/public/table/{$fixture['token']}/cart")
            ->assertStatus(401);
    }
}
