<?php

declare(strict_types=1);

namespace Tests\Feature\Sale;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class ChargeSessionTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithLines(array $tenant, int $diners = 4, int $totalCents = 10000): array
    {

        $familyId = DB::table('families')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Family',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taxId = DB::table('taxes')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Tax',
            'percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productUuid = (string) Str::uuid();
        $productPrice = 1000;

        $productId = DB::table('products')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => $productUuid,
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Test Product',
            'price' => $productPrice,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Zone',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tableId = DB::table('tables')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'zone_id' => $zoneId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Table',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderUuid = (string) Str::uuid();

        $userId = DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id');

        $orderId = DB::table('orders')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => $orderUuid,
            'status' => 'open',
            'table_id' => $tableId,
            'diners' => $diners,
            'opened_by_user_id' => $userId,
            'opened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quantity = (int) ceil($totalCents / $productPrice);
        for ($i = 0; $i < $quantity; $i++) {
            DB::table('order_lines')->insert([
                'restaurant_id' => $tenant['restaurant_id'],
                'uuid' => (string) Str::uuid(),
                'order_id' => $orderId,
                'product_id' => $productId,
                'user_id' => $userId,
                'price' => $productPrice,
                'quantity' => 1,
                'tax_percentage' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['id' => $orderId, 'uuid' => $orderUuid];
    }

    public function test_create_charge_session_returns_201_with_valid_data(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);

        $response->assertStatus(201);
        $response->assertJson([
            'diners_count' => 4,
            'total_cents' => 10000,
            'paid_cents' => 0,
            'remaining_cents' => 10000,
            'suggested_per_diner_cents' => 2500,
            'status' => 'active',
            'paid_diner_numbers' => [],
        ]);
        $response->assertJsonStructure([
            'id',
            'order_id',
            'diners_count',
            'total_cents',
            'paid_cents',
            'remaining_cents',
            'suggested_per_diner_cents',
            'status',
            'created_at',
            'updated_at',
        ]);
    }

    public function test_create_charge_session_returns_existing_session_without_recalculation(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $response1 = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $response1->assertStatus(201);
        $sessionId = $response1->json('id');

        $response2 = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 5,

        ]);
        $response2->assertStatus(201);

        $this->assertEquals($sessionId, $response2->json('id'));
        $this->assertEquals(4, $response2->json('diners_count'));

        $this->assertEquals(2500, $response2->json('suggested_per_diner_cents'));

    }

    public function test_create_charge_session_returns_422_when_missing_fields(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'opened_by_user_id']);
    }

    public function test_create_charge_session_returns_422_when_invalid_uuids(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => 'not-a-uuid',
            'opened_by_user_id' => 'not-a-uuid',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['order_id', 'opened_by_user_id']);
    }

    public function test_create_charge_session_returns_422_when_order_not_found(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa',
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Order not found.']);
    }

    public function test_get_active_charge_session_returns_200_when_session_exists(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);

        $response = $this->withSession($tenant['session'])->getJson(
            '/api/tpv/charge-sessions/current?order_id='.$order['uuid']
        );

        $response->assertStatus(200);
        $response->assertJson([
            'order_id' => $order['uuid'],
            'status' => 'active',
        ]);
    }

    public function test_get_current_charge_session_returns_404_when_no_session(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $response = $this->withSession($tenant['session'])->getJson(
            '/api/tpv/charge-sessions/current?order_id='.$order['uuid']
        );

        $response->assertStatus(404);
        $response->assertJsonPath('message', fn ($m) => str_contains($m, 'not found.'));
    }

    public function test_record_payment_returns_201_and_updates_session(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        $this->createCashSessionForTests($tenant, 'test-device-001');

        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );

        $response->assertStatus(201);
        $response->assertJson([
            'diner_number' => 1,
            'amount_cents' => 2500,
            'payment_method' => 'cash',
            'session_paid_diners_count' => 1,
            'is_session_complete' => false,
        ]);
    }

    public function test_record_payment_returns_200_when_session_completes(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 2,
        ]);
        $sessionId = $createResponse->json('id');

        $this->createCashSessionForTests($tenant, 'test-device-001');

        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );

        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 2,
                'payment_method' => 'card',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );

        $response->assertStatus(200);

        $response->assertJson([
            'session_status' => 'completed',
            'is_session_complete' => true,
        ]);
    }

    public function test_record_payment_returns_422_when_session_completed(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 1,
        ]);
        $sessionId = $createResponse->json('id');

        $this->createCashSessionForTests($tenant, 'test-device-001');

        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );

        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Charge session is not active.']);
    }

    public function test_update_diners_returns_200_when_no_payments(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        $response = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 5]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'diners_count' => 5,
            'suggested_per_diner_cents' => 2000,

            'status' => 'active',
        ]);
    }

    public function test_update_diners_succeeds_when_has_payments(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        $this->createCashSessionForTests($tenant, 'test-device-001');

        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        )->assertStatus(201);

        $response = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 5]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'diners_count' => 5,
            'status' => 'active',
        ]);
    }

    public function test_update_diners_returns_422_when_below_paid_count(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        $this->createCashSessionForTests($tenant, 'test-device-001');

        foreach ([1, 2] as $diner) {
            $this->withSession($tenant['session'])->postJson(
                "/api/tpv/charge-sessions/{$sessionId}/payments",
                [
                    'diner_number' => $diner,
                    'payment_method' => 'cash',
                    'opened_by_user_id' => $tenant['user_uuid'],
                    'closed_by_user_id' => $tenant['user_uuid'],
                    'device_id' => 'test-device-001',
                ]
            )->assertStatus(201);
        }

        $response = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 1]
        );

        $response->assertStatus(422);
        $response->assertJsonPath('message', fn ($m) => str_contains($m, 'already-paid count'));
    }

    public function test_cancel_session_returns_200_and_deactivates_session(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/cancel",
            [
                'cancelled_by_user_id' => $tenant['user_uuid'],
                'reason' => 'Cliente cambió de opinión',
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'cancelled',
            'paid_diners_count' => 0,
            'warning_message' => null,
        ]);
    }

    public function test_cancel_session_returns_warning_when_has_payments(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        $this->createCashSessionForTests($tenant, 'test-device-001');

        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );

        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/cancel",
            [
                'cancelled_by_user_id' => $tenant['user_uuid'],
                'reason' => 'Error en cuenta',
            ]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'cancelled',
            'paid_diners_count' => 1,
        ]);
        $response->assertJsonPath('warning_message', function ($message) {
            return str_contains($message, 'ATENCIÓN');
        });
    }

    public function test_cancel_session_returns_422_when_already_cancelled(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/cancel",
            [
                'cancelled_by_user_id' => $tenant['user_uuid'],
            ]
        );

        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/cancel",
            [
                'cancelled_by_user_id' => $tenant['user_uuid'],
            ]
        );

        $response->assertStatus(409);
        $response->assertJson(['message' => 'Charge session is not active.']);
    }

    public function test_complete_workflow_as_per_specification(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant, diners: 4, totalCents: 10000);

        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $createResponse->assertStatus(201);
        $this->assertEquals(2500, $createResponse->json('suggested_per_diner_cents'));

        $sessionId = $createResponse->json('id');

        $updateResponse = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 3]
        );
        $updateResponse->assertStatus(200);
        $this->assertEquals(3333, $updateResponse->json('suggested_per_diner_cents'));

        $this->createCashSessionForTests($tenant, 'test-device-001');

        $payment1Response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );
        $payment1Response->assertStatus(201);
        $this->assertEquals(3333, $payment1Response->json('amount_cents'));

        $updateOkResponse = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 2]
        );
        $updateOkResponse->assertStatus(200);
        $this->assertEquals(2, $updateOkResponse->json('diners_count'));

        $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 3]
        )->assertStatus(200);

        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 2,
                'payment_method' => 'card',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 3,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );

        $getResponse = $this->withSession($tenant['session'])->getJson(
            '/api/tpv/charge-sessions/current?order_id='.$order['uuid']
        );
        $getResponse->assertStatus(200);
        $this->assertEquals('completed', $getResponse->json('status'));
        $this->assertEquals(3, count($getResponse->json('paid_diner_numbers')));

        $lastPaymentResponse = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'test-device-001',
            ]
        );
        $lastPaymentResponse->assertStatus(409);

    }
}
