<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class OrderAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{restaurantUuid: string, tableUuid: string, userUuid: string, session: array} */
    private function setUpTenant(): array
    {
        $restaurantUuid = (string) Str::uuid();
        $zoneUuid = (string) Str::uuid();
        $tableUuid = (string) Str::uuid();
        $userUuid = (string) Str::uuid();

        $restaurantId = DB::table('restaurants')->insertGetId([
            'uuid' => $restaurantUuid,
            'name' => 'Order Audit Test',
            'legal_name' => 'Order Audit Test S.L.',
            'tax_id' => 'B88888888',
            'email' => 'order.audit@local.dev',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $zoneId = DB::table('zones')->insertGetId([
            'uuid' => $zoneUuid,
            'restaurant_id' => $restaurantId,
            'name' => 'Zona Test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('tables')->insert([
            'uuid' => $tableUuid,
            'restaurant_id' => $restaurantId,
            'zone_id' => $zoneId,
            'name' => 'Mesa 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->insert([
            'uuid' => $userUuid,
            'restaurant_id' => $restaurantId,
            'role' => 'admin',
            'image_src' => null,
            'name' => 'Waiter Test',
            'email' => 'waiter.order.audit@test.dev',
            'pin' => '1234',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'restaurantUuid' => $restaurantUuid,
            'tableUuid' => $tableUuid,
            'userUuid' => $userUuid,
            'session' => ['auth_user_id' => $userUuid],
        ];
    }

    public function test_creating_an_order_writes_order_created_audit_log(): void
    {
        $data = $this->setUpTenant();

        $response = $this->withSession($data['session'])->postJson('/api/tpv/orders', [
            'table_id' => $data['tableUuid'],
            'opened_by_user_id' => $data['userUuid'],
            'diners' => 3,
        ]);

        $response->assertStatus(201);
        $orderId = $response->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'order',
            'entity_id' => $orderId,
            'action' => 'order.created',
        ]);
    }

    public function test_cancelling_an_order_writes_order_cancelled_audit_log(): void
    {
        $data = $this->setUpTenant();

        $orderId = $this->withSession($data['session'])->postJson('/api/tpv/orders', [
            'table_id' => $data['tableUuid'],
            'opened_by_user_id' => $data['userUuid'],
            'diners' => 2,
        ])->json('id');

        $this->withSession($data['session'])->postJson("/api/tpv/orders/{$orderId}/cancel", [
            'cancelled_by_user_id' => $data['userUuid'],
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'order',
            'entity_id' => $orderId,
            'action' => 'order.cancelled',
        ]);
    }

    public function test_marking_order_to_charge_writes_audit_log(): void
    {
        $data = $this->setUpTenant();

        $orderId = $this->withSession($data['session'])->postJson('/api/tpv/orders', [
            'table_id' => $data['tableUuid'],
            'opened_by_user_id' => $data['userUuid'],
            'diners' => 4,
        ])->json('id');

        $this->withSession($data['session'])->postJson("/api/tpv/orders/{$orderId}/mark-to-charge", [
            'closed_by_user_id' => $data['userUuid'],
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'order',
            'entity_id' => $orderId,
            'action' => 'order.marked_to_charge',
        ]);
    }

    public function test_updating_diners_writes_order_diners_updated_audit_log(): void
    {
        $data = $this->setUpTenant();

        $orderId = $this->withSession($data['session'])->postJson('/api/tpv/orders', [
            'table_id' => $data['tableUuid'],
            'opened_by_user_id' => $data['userUuid'],
            'diners' => 2,
        ])->json('id');

        $this->withSession($data['session'])->putJson("/api/tpv/orders/{$orderId}", [
            'diners' => 5,
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'order',
            'entity_id' => $orderId,
            'action' => 'order.diners_updated',
        ]);
    }

    public function test_reopening_an_order_writes_order_reopened_audit_log(): void
    {
        $data = $this->setUpTenant();

        $orderId = $this->withSession($data['session'])->postJson('/api/tpv/orders', [
            'table_id' => $data['tableUuid'],
            'opened_by_user_id' => $data['userUuid'],
            'diners' => 3,
        ])->json('id');

        $this->withSession($data['session'])->postJson("/api/tpv/orders/{$orderId}/mark-to-charge", [
            'closed_by_user_id' => $data['userUuid'],
        ])->assertStatus(200);

        $this->withSession($data['session'])->postJson("/api/tpv/orders/{$orderId}/reopen", [
            'reopened_by_user_id' => $data['userUuid'],
        ])->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'order',
            'entity_id' => $orderId,
            'action' => 'order.reopened',
        ]);
    }

    public function test_deleting_an_order_writes_order_deleted_audit_log(): void
    {
        $data = $this->setUpTenant();

        $orderId = $this->withSession($data['session'])->postJson('/api/tpv/orders', [
            'table_id' => $data['tableUuid'],
            'opened_by_user_id' => $data['userUuid'],
            'diners' => 2,
        ])->json('id');

        $this->withSession($data['session'])->deleteJson("/api/tpv/orders/{$orderId}")
            ->assertStatus(204);

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'order',
            'entity_id' => $orderId,
            'action' => 'order.deleted',
        ]);
    }
}
