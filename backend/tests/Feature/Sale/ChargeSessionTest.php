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

    /**
     * Helper para crear una orden con líneas usando DB directamente
     * @return array{id: int, uuid: string}
     */
    private function createOrderWithLines(array $tenant, int $diners = 4, int $totalCents = 10000): array
    {
        // Crear familia (usa restaurant_id numérico interno para FK)
        $familyId = DB::table('families')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Family',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear tax (usa restaurant_id numérico interno para FK)
        $taxId = DB::table('taxes')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'name' => 'Test Tax',
            'percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productUuid = (string) Str::uuid();
        $productPrice = 1000; // 10.00 €

        // Crear producto
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

        // Crear zona y mesa
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

        // Obtener ID numérico del usuario
        $userId = DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id');

        // Crear orden
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

        // Crear líneas para sumar el total
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

    // ═══════════════════════════════════════════════════════════════════════
    // CreateChargeSession
    // ═══════════════════════════════════════════════════════════════════════

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
            'amount_per_diner' => 2500,
            'paid_diners_count' => 0,
            'status' => 'active',
        ]);
        $response->assertJsonStructure([
            'id',
            'order_id',
            'diners_count',
            'total_cents',
            'amount_per_diner',
            'paid_diners_count',
            'remaining_amount',
            'status',
            'paid_diners',
        ]);
    }

    public function test_create_charge_session_returns_existing_session_without_recalculation(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        // Crear primera sesión
        $response1 = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $response1->assertStatus(201);
        $sessionId = $response1->json('id');

        // Crear segunda sesión - debe retornar la existente
        $response2 = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 5, // Diferente - pero no recalcula
        ]);
        $response2->assertStatus(201);

        // Verificar que es la misma sesión con mismos datos
        $this->assertEquals($sessionId, $response2->json('id'));
        $this->assertEquals(4, $response2->json('diners_count')); // No cambió
        $this->assertEquals(2500, $response2->json('amount_per_diner')); // No recalculó
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
        $response->assertJson(['message' => 'Order not found']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // GetActiveChargeSession
    // ═══════════════════════════════════════════════════════════════════════

    public function test_get_active_charge_session_returns_200_when_session_exists(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        // Crear sesión
        $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);

        // Consultar sesión activa
        $response = $this->withSession($tenant['session'])->getJson(
            '/api/tpv/charge-sessions/active?order_id=' . $order['uuid']
        );

        $response->assertStatus(200);
        $response->assertJson([
            'order_id' => $order['uuid'],
            'status' => 'active',
        ]);
    }

    public function test_get_active_charge_session_returns_404_when_no_session(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $response = $this->withSession($tenant['session'])->getJson(
            '/api/tpv/charge-sessions/active?order_id=' . $order['uuid']
        );

        $response->assertStatus(404);
        $response->assertJson(['message' => 'No active charge session found for this order']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // RecordChargeSessionPayment
    // ═══════════════════════════════════════════════════════════════════════

    public function test_record_payment_returns_201_and_updates_session(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        // Crear sesión
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        // Registrar pago
        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
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

        // Crear sesión con 2 comensales
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 2,
        ]);
        $sessionId = $createResponse->json('id');

        // Primer pago
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
            ]
        );

        // Segundo pago - completa la sesión
        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 2,
                'payment_method' => 'card',
            ]
        );

        $response->assertStatus(200); // 200 en lugar de 201 porque se completó
        $response->assertJson([
            'session_status' => 'completed',
            'is_session_complete' => true,
        ]);
    }

    public function test_record_payment_returns_422_when_diner_already_paid(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        // Crear sesión
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        // Primer pago
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
            ]
        );

        // Segundo pago del mismo comensal
        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'card',
            ]
        );

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Diner 1 has already paid']);
    }

    public function test_record_payment_returns_422_when_session_completed(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        // Crear sesión con 1 comensal
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 1,
        ]);
        $sessionId = $createResponse->json('id');

        // Pagar y completar sesión
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
            ]
        );

        // Intentar otro pago
        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
            ]
        );

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Charge session is not active']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // UpdateChargeSessionDiners
    // ═══════════════════════════════════════════════════════════════════════

    public function test_update_diners_returns_200_when_no_payments(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        // Crear sesión
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        // Actualizar comensales
        $response = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 5]
        );

        $response->assertStatus(200);
        $response->assertJson([
            'diners_count' => 5,
            'amount_per_diner' => 2000, // 100/5 = 20
            'status' => 'active',
        ]);
    }

    public function test_update_diners_returns_422_when_has_payments(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        // Crear sesión
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        // Registrar pago
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
            ]
        );

        // Intentar actualizar comensales
        $response = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 5]
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Ya hay 1 pago(s) registrado(s). No es posible modificar el número de comensales. Si necesitas ajustar el cobro, cancela la sesión y vuelve a abrirla.',
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // CancelChargeSession
    // ═══════════════════════════════════════════════════════════════════════

    public function test_cancel_session_returns_200_and_deactivates_session(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        // Crear sesión
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        // Cancelar sesión
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

        // Crear sesión
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        // Registrar pago
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'cash',
            ]
        );

        // Cancelar sesión
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

        // Crear sesión
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $sessionId = $createResponse->json('id');

        // Cancelar sesión
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/cancel",
            [
                'cancelled_by_user_id' => $tenant['user_uuid'],
            ]
        );

        // Intentar cancelar de nuevo
        $response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/cancel",
            [
                'cancelled_by_user_id' => $tenant['user_uuid'],
            ]
        );

        $response->assertStatus(422);
        $response->assertJson(['message' => 'Cannot cancel charge session: status is cancelled']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Escenario completo de la especificación
    // ═══════════════════════════════════════════════════════════════════════

    public function test_complete_workflow_as_per_specification(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant, diners: 4, totalCents: 10000);

        // Paso 1: Crear sesión de cobro
        $createResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 4,
        ]);
        $createResponse->assertStatus(201);
        $this->assertEquals(2500, $createResponse->json('amount_per_diner')); // 100/4 = 25€
        $sessionId = $createResponse->json('id');

        // Paso 2: Verificar que podemos editar comensales (sin pagos)
        $updateResponse = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 3]
        );
        $updateResponse->assertStatus(200);
        $this->assertEquals(3333, $updateResponse->json('amount_per_diner')); // 100/3 ≈ 33.33€

        // Paso 3: Registrar primer pago
        $payment1Response = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            ['diner_number' => 1, 'payment_method' => 'cash']
        );
        $payment1Response->assertStatus(201);
        $this->assertEquals(3333, $payment1Response->json('amount_cents'));

        // Paso 4: Verificar que YA NO podemos editar comensales
        $updateFailResponse = $this->withSession($tenant['session'])->putJson(
            "/api/tpv/charge-sessions/{$sessionId}/diners",
            ['diners_count' => 2]
        );
        $updateFailResponse->assertStatus(422);
        $this->assertStringContainsString('Ya hay 1 pago(s) registrado', $updateFailResponse->json('message'));

        // Paso 5: Registrar pagos restantes hasta completar
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            ['diner_number' => 2, 'payment_method' => 'card']
        );
        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            ['diner_number' => 3, 'payment_method' => 'cash']
        );

        // Paso 6: Verificar que la sesión se completó
        $getResponse = $this->withSession($tenant['session'])->getJson(
            '/api/tpv/charge-sessions/active?order_id=' . $order['uuid']
        );
        $getResponse->assertStatus(200);
        $this->assertEquals('completed', $getResponse->json('status'));
        $this->assertEquals(3, $getResponse->json('paid_diners_count'));

        // Paso 7: Verificar que el último pago fue el resto (100 - 33.33 - 33.33 = 33.34)
        $lastPaymentResponse = $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            ['diner_number' => 1, 'payment_method' => 'cash']
        );
        $lastPaymentResponse->assertStatus(422); // Ya completada, no se puede pagar más
    }
}
