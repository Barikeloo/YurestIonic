<?php

namespace Tests\Feature\Reporting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class GetSaleDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_sale_detail_with_lines_and_payments(): void
    {
        $tenant = $this->createTenantSession('admin');
        $userId = DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id');

        $productUuid = (string) Str::uuid();
        $familyUuid  = (string) Str::uuid();
        $taxId = DB::table('taxes')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $tenant['restaurant_id'],
            'name' => 'IVA reducido',
            'percentage' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $familyId = DB::table('families')->insertGetId([
            'uuid' => $familyUuid,
            'restaurant_id' => $tenant['restaurant_id'],
            'name' => 'Bebidas',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'uuid'          => $productUuid,
            'restaurant_id' => $tenant['restaurant_id'],
            'family_id'     => $familyId,
            'tax_id'        => $taxId,
            'name'          => 'Caña',
            'price'         => 200,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $saleUuid = (string) Str::uuid();
        $saleId   = DB::table('sales')->insertGetId([
            'uuid'             => $saleUuid,
            'restaurant_id'    => $tenant['restaurant_id'],
            'ticket_number'    => 42,
            'status'           => 'closed',
            'value_date'       => now()->subHours(2),
            'total'            => 360,
            'opened_by_user_id' => $userId,
            'document_type'    => 'simplified',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        DB::table('sales_lines')->insert([
            'uuid'           => (string) Str::uuid(),
            'restaurant_id'  => $tenant['restaurant_id'],
            'sale_id'        => $saleId,
            'product_id'     => $productId,
            'user_id'        => $userId,
            'quantity'       => 2,
            'price'          => 180,
            'tax_percentage' => 10,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $cashId = DB::table('cash_sessions')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'restaurant_id' => $tenant['restaurant_id'],
            'device_id' => 'test-device',
            'opened_by_user_id' => $userId,
            'opened_at' => now(),
            'status' => 'open',
            'initial_amount_cents' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('sale_payments')->insert([
            'uuid'           => (string) Str::uuid(),
            'sale_id'        => $saleId,
            'restaurant_id'  => $tenant['restaurant_id'],
            'cash_session_id' => $cashId,
            'method'         => 'card',
            'amount_cents'   => 300,
            'user_id'        => $userId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $response = $this->withSession($tenant['session'])
            ->getJson("/api/admin/reports/sales/{$saleUuid}")
            ->assertStatus(200);

        $response->assertJsonStructure([
            'uuid', 'ticket_number', 'value_date', 'status',
            'zone_name', 'table_name', 'diners', 'opened_by',
            'duration_minutes',
            'lines' => [['product_name', 'family_name', 'qty', 'unit_price', 'tax_pct', 'total']],
            'payments' => [['method', 'amount']],
            'tax_breakdown' => [['rate', 'base', 'tax']],
            'subtotal', 'tax_total', 'tips_total', 'cancel_reason',
        ]);

        $this->assertSame(42, $response->json('ticket_number'));
        $this->assertSame('closed', $response->json('status'));
        $this->assertCount(1, $response->json('lines'));
        $this->assertCount(1, $response->json('payments'));
        $this->assertSame('Caña', $response->json('lines.0.product_name'));
        $this->assertSame(2, $response->json('lines.0.qty'));
        $this->assertSame(0, $response->json('tips_total'));
    }

    public function test_returns_404_when_sale_not_found(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/sales/' . Str::uuid())
            ->assertStatus(404)
            ->assertJson(['error' => 'Sale not found.']);
    }

    public function test_requires_admin_session(): void
    {
        $this->getJson('/api/admin/reports/sales/' . Str::uuid())
            ->assertStatus(401);
    }

    public function test_returns_cancelled_sale(): void
    {
        $tenant = $this->createTenantSession('admin');
        $userId = DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id');

        $saleUuid = (string) Str::uuid();
        DB::table('sales')->insert([
            'uuid'               => $saleUuid,
            'restaurant_id'      => $tenant['restaurant_id'],
            'ticket_number'      => 99,
            'status'             => 'cancelled',
            'value_date'         => now()->subDay(),
            'total'              => 0,
            'opened_by_user_id'  => $userId,
            'cancelled_by_user_id' => $userId,
            'cancelled_at'       => now(),
            'cancel_reason'      => 'Error en pedido',
            'document_type'      => 'simplified',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $response = $this->withSession($tenant['session'])
            ->getJson("/api/admin/reports/sales/{$saleUuid}")
            ->assertStatus(200);

        $this->assertSame('cancelled', $response->json('status'));
        $this->assertSame('Error en pedido', $response->json('cancel_reason'));
    }
}
