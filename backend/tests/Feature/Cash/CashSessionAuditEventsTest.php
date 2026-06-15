<?php

declare(strict_types=1);

namespace Tests\Feature\Cash;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class CashSessionAuditEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_open_cash_session_writes_audit_log(): void
    {
        $tenant = $this->createTenantSession();

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/cash-sessions', [
            'device_id' => 'device-test-001',
            'opened_by_user_id' => $tenant['user_uuid'],
            'initial_amount_cents' => 50000,
            'notes' => null,
        ]);

        $response->assertStatus(201);
        $sessionId = $response->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'cash_session',
            'entity_id' => $sessionId,
            'action' => 'caja.opened',
        ]);
    }

    public function test_register_cash_movement_writes_audit_log(): void
    {
        $tenant = $this->createTenantSession();

        $cashSessionUuid = (string) Str::uuid();
        DB::table('cash_sessions')->insert([
            'restaurant_id' => $tenant['restaurant_id'],
            'uuid' => $cashSessionUuid,
            'device_id' => 'device-test-001',
            'opened_by_user_id' => DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id'),
            'opened_at' => now(),
            'closed_at' => null,
            'initial_amount_cents' => 0,
            'final_amount_cents' => null,
            'expected_amount_cents' => null,
            'discrepancy_cents' => null,
            'discrepancy_reason' => null,
            'z_report_number' => null,
            'z_report_hash' => null,
            'notes' => null,
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->withSession($tenant['session'])->postJson('/api/tpv/cash-movements', [
            'cash_session_id' => $cashSessionUuid,
            'type' => 'in',
            'reason_code' => 'change_refill',
            'amount_cents' => 5000,
            'user_id' => $tenant['user_uuid'],
            'description' => null,
        ]);

        $response->assertStatus(201);
        $movementId = $response->json('id');

        $this->assertDatabaseHas('audit_logs', [
            'entity_type' => 'cash_movement',
            'entity_id' => $movementId,
            'action' => 'caja.cash_movement',
        ]);
    }
}
