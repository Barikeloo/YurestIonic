<?php

declare(strict_types=1);

namespace Tests\Feature\Sale;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class SaleAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    private function createOrderWithLines(array $tenant, int $diners = 2, int $totalCents = 2000): array
    {
        $familyId = DB::table('families')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'name' => 'Bebidas',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $taxId = DB::table('taxes')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'name' => 'IVA 10',
            'percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $productId = DB::table('products')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'family_id' => $familyId,
            'tax_id' => $taxId,
            'name' => 'Café',
            'price' => 1000,
            'stock' => 50,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => (string) Str::uuid(),
            'name' => 'Terraza',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tableId = DB::table('tables')->insertGetId([
            'restaurant_id' => $tenant['restaurant_id'],
            'zone_id' => $zoneId,
            'uuid' => (string) Str::uuid(),
            'name' => 'Mesa 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $userId = DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id');

        $orderUuid = (string) Str::uuid();
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

        $quantity = (int) ceil($totalCents / 1000);
        for ($i = 0; $i < $quantity; $i++) {
            DB::table('order_lines')->insert([
                'restaurant_id' => $tenant['restaurant_id'],
                'uuid' => (string) Str::uuid(),
                'order_id' => $orderId,
                'product_id' => $productId,
                'user_id' => $userId,
                'price' => 1000,
                'quantity' => 1,
                'tax_percentage' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['id' => $orderId, 'uuid' => $orderUuid];
    }

    public function test_create_sale_writes_audit_log(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);
        $this->createCashSessionForTests($tenant, 'device-001');

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/sales', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'closed_by_user_id' => $tenant['user_uuid'],
            'device_id' => 'device-001',
            'payments' => [['method' => 'cash', 'amount_cents' => 2000]],
        ]);

        $response->assertStatus(201);
        $saleId = $response->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'sale',
            'entity_id' => $saleId,
            'action' => 'sale.created',
        ]);
    }

    public function test_charge_session_created_writes_audit_log(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 2,
        ]);

        $response->assertStatus(201);
        $sessionId = $response->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'charge_session',
            'entity_id' => $sessionId,
            'action' => 'sale.charge_session_created',
        ]);
    }

    public function test_charge_session_payment_writes_audit_log(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);

        $sessionResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/charge-sessions', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'diners_count' => 2,
        ]);
        $sessionId = $sessionResponse->json('id');

        $this->createCashSessionForTests($tenant, 'device-001');

        $this->withSession($tenant['session'])->postJson(
            "/api/tpv/charge-sessions/{$sessionId}/payments",
            [
                'diner_number' => 1,
                'payment_method' => 'card',
                'opened_by_user_id' => $tenant['user_uuid'],
                'closed_by_user_id' => $tenant['user_uuid'],
                'device_id' => 'device-001',
            ]
        )->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'charge_session',
            'entity_id' => $sessionId,
            'action' => 'sale.payment_recorded',
        ]);
    }

    public function test_cancel_sale_writes_audit_log(): void
    {
        $tenant = $this->createTenantSession();
        $order = $this->createOrderWithLines($tenant);
        $this->createCashSessionForTests($tenant, 'device-001');

        $saleResponse = $this->withSession($tenant['session'])->postJson('/api/tpv/sales', [
            'order_id' => $order['uuid'],
            'opened_by_user_id' => $tenant['user_uuid'],
            'closed_by_user_id' => $tenant['user_uuid'],
            'device_id' => 'device-001',
            'payments' => [['method' => 'card', 'amount_cents' => 2000]],
        ]);
        $saleId = $saleResponse->json('id');

        $this->withSession($tenant['session'])->postJson('/api/tpv/sales/cancel', [
            'sale_id' => $saleId,
            'cancelled_by_user_id' => $tenant['user_uuid'],
            'reason' => 'Error del cliente',
        ])->assertSuccessful();

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'sale',
            'entity_id' => $saleId,
            'action' => 'sale.cancelled',
        ]);
    }
}
