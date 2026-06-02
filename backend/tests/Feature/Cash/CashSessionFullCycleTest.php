<?php

namespace Tests\Feature\Cash;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashSessionFullCycleTest extends TestCase
{
    use RefreshDatabase;

    private array $tenant;
    private string $deviceId = 'test-device-001';
    private string $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->createTenantSession();
        $this->userId = $this->tenant['user_uuid'];
    }

    private function api(string $path): string
    {
        return '/api' . $path;
    }

    public function test_cash_session_full_cycle_flow(): void
    {
        // 1. Open session
        $openResponse = $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions'), [
                'device_id' => $this->deviceId,
                'opened_by_user_id' => $this->userId,
                'initial_amount_cents' => 50000,
                'notes' => 'Morning shift',
            ]);
        $openResponse->assertStatus(201);
        $openResponse->assertJsonStructure([
            'id', 'uuid', 'device_id', 'opened_by_user_id', 'opened_at',
            'initial_amount_cents', 'status', 'notes',
        ]);
        $openResponse->assertJson([
            'status' => 'open',
            'initial_amount_cents' => 50000,
            'device_id' => $this->deviceId,
            'notes' => 'Morning shift',
        ]);
        $sessionUuid = $openResponse->json('uuid');
        $this->assertNotEmpty($sessionUuid);

        // 2. Get active session
        $this->withSession($this->tenant['session'])
            ->getJson($this->api('/tpv/cash-sessions/active?device_id=' . $this->deviceId))
            ->assertStatus(200)
            ->assertJson([
                'uuid' => $sessionUuid,
                'status' => 'open',
            ]);

        // 3. Active session 204 when no session for different device
        $this->withSession($this->tenant['session'])
            ->getJson($this->api('/tpv/cash-sessions/active?device_id=other-device'))
            ->assertStatus(204);

        // 4. Open duplicate returns 409
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions'), [
                'device_id' => $this->deviceId,
                'opened_by_user_id' => $this->userId,
                'initial_amount_cents' => 10000,
            ])
            ->assertStatus(409);

        // 5. Register cash movement (in)
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-movements'), [
                'cash_session_id' => $sessionUuid,
                'type' => 'in',
                'reason_code' => 'change_refill',
                'amount_cents' => 10000,
                'user_id' => $this->userId,
                'description' => 'Change refill',
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['id', 'cash_session_id', 'type', 'amount_cents', 'reason_code'])
            ->assertJson([
                'type' => 'in',
                'amount_cents' => 10000,
                'reason_code' => 'change_refill',
            ]);

        // 6. Register cash movement (out)
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-movements'), [
                'cash_session_id' => $sessionUuid,
                'type' => 'out',
                'reason_code' => 'sangria',
                'amount_cents' => 5000,
                'user_id' => $this->userId,
            ])
            ->assertStatus(201);

        // 7. List movements
        $this->withSession($this->tenant['session'])
            ->getJson($this->api('/tpv/cash-movements?cash_session_id=' . $sessionUuid))
            ->assertStatus(200)
            ->assertJsonCount(2, 'movements')
            ->assertJsonFragment(['type' => 'in', 'amount_cents' => 10000])
            ->assertJsonFragment(['type' => 'out', 'amount_cents' => 5000]);

        // 8. Start closing
        $startCloseResponse = $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions/start-closing'), [
                'cash_session_id' => $sessionUuid,
                'device_id' => $this->deviceId,
            ]);
        $startCloseResponse->assertStatus(200);
        $startCloseResponse->assertJson([
            'id' => $sessionUuid,
            'status' => 'closing',
        ]);

        // 9. Cancel closing
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions/cancel-closing'), [
                'cash_session_id' => $sessionUuid,
            ])
            ->assertStatus(200)
            ->assertJson([
                'id' => $sessionUuid,
                'status' => 'open',
            ]);

        // 10. Start closing again
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions/start-closing'), [
                'cash_session_id' => $sessionUuid,
            ])
            ->assertStatus(200)
            ->assertJson(['status' => 'closing']);

        // 11. Close session
        $closeResponse = $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions/close'), [
                'cash_session_id' => $sessionUuid,
                'closed_by_user_id' => $this->userId,
                'final_amount_cents' => 65000,
            ]);
        $closeResponse->assertStatus(200);
        $closeResponse->assertJsonStructure([
            'id', 'uuid', 'status', 'z_report_number', 'z_report',
        ]);
        $closeResponse->assertJson([
            'status' => 'closed',
            'uuid' => $sessionUuid,
        ]);
        $zReportNumber = $closeResponse->json('z_report_number');
        $this->assertNotNull($zReportNumber);

        // 12. List sessions (closed)
        $listResponse = $this->withSession($this->tenant['session'])
            ->getJson($this->api('/tpv/cash-sessions'));
        $listResponse->assertStatus(200);
        $listResponse->assertJsonCount(1, 'sessions');
        $listResponse->assertJsonFragment(['uuid' => $sessionUuid, 'status' => 'closed']);

        // 13. Get last closed
        $lastClosedResponse = $this->withSession($this->tenant['session'])
            ->getJson($this->api('/tpv/cash-sessions/last-closed'));
        $lastClosedResponse->assertStatus(200);
        $lastClosedResponse->assertJsonStructure([
            'last_closed' => ['id', 'opened_by_user_id', 'z_report_number', 'tickets'],
            'orphan_session',
        ]);
        $this->assertNotNull($lastClosedResponse->json('last_closed'));
        $this->assertNull($lastClosedResponse->json('orphan_session'));

        // 14. Get session summary
        $this->withSession($this->tenant['session'])
            ->getJson($this->api('/tpv/cash-sessions/' . $sessionUuid . '/summary'))
            ->assertStatus(200)
            ->assertJson([
                'uuid' => $sessionUuid,
                'status' => 'closed',
                'initial_amount_cents' => 50000,
                'total_in_movements' => 10000,
                'total_out_movements' => 5000,
            ]);

        // 15. Get Z report
        $zReportUuid = $closeResponse->json('z_report.id');
        $this->withSession($this->tenant['session'])
            ->getJson($this->api('/tpv/z-reports/' . $zReportUuid))
            ->assertStatus(200)
            ->assertJsonStructure([
                'id', 'cash_session_id', 'report_number', 'report_hash',
                'total_sales_cents', 'total_cash_cents', 'total_card_cents',
                'total_other_cents', 'sales_count', 'cancelled_sales_count',
            ])
            ->assertJson([
                'report_number' => $zReportNumber,
                'cash_session_id' => $sessionUuid,
            ]);
    }

    public function test_open_session_validation_errors(): void
    {
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions'), [])
            ->assertStatus(422);

        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions'), [
                'device_id' => $this->deviceId,
                'opened_by_user_id' => $this->userId,
                'initial_amount_cents' => -1,
            ])
            ->assertStatus(422);

        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions'), [
                'device_id' => $this->deviceId,
                'opened_by_user_id' => 'not-a-uuid',
                'initial_amount_cents' => 0,
            ])
            ->assertStatus(422);
    }

    public function test_close_session_not_found_returns_404(): void
    {
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions/close'), [
                'cash_session_id' => '00000000-0000-4000-8000-000000000000',
                'closed_by_user_id' => $this->userId,
                'final_amount_cents' => 10000,
            ])
            ->assertStatus(404);
    }

    public function test_force_close_session_by_admin(): void
    {
        $adminTenant = $this->createTenantSession('admin');
        $adminUserId = $adminTenant['user_uuid'];

        $openResponse = $this->withSession($adminTenant['session'])
            ->postJson($this->api('/tpv/cash-sessions'), [
                'device_id' => $this->deviceId,
                'opened_by_user_id' => $adminUserId,
                'initial_amount_cents' => 50000,
            ]);
        $openResponse->assertStatus(201);
        $sessionUuid = $openResponse->json('uuid');

        $this->withSession($adminTenant['session'])
            ->postJson($this->api('/tpv/cash-sessions/force-close'), [
                'cash_session_id' => $sessionUuid,
                'closed_by_user_id' => $adminUserId,
            ])
            ->assertStatus(200)
            ->assertJson([
                'uuid' => $sessionUuid,
                'status' => 'abandoned',
            ]);
    }

    public function test_register_movement_validation_errors(): void
    {
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-movements'), [])
            ->assertStatus(422);

        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-movements'), [
                'cash_session_id' => '00000000-0000-4000-8000-000000000000',
                'type' => 'invalid',
                'reason_code' => 'bad_code',
                'amount_cents' => 100,
                'user_id' => $this->userId,
            ])
            ->assertStatus(422);
    }

    public function test_start_closing_session_not_found_returns_404(): void
    {
        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions/start-closing'), [
                'cash_session_id' => '00000000-0000-4000-8000-000000000000',
            ])
            ->assertStatus(404);
    }

    public function test_cancel_closing_when_not_closing_returns_409(): void
    {
        $openResponse = $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions'), [
                'device_id' => $this->deviceId,
                'opened_by_user_id' => $this->userId,
                'initial_amount_cents' => 0,
            ]);
        $sessionUuid = $openResponse->json('uuid');

        $this->withSession($this->tenant['session'])
            ->postJson($this->api('/tpv/cash-sessions/cancel-closing'), [
                'cash_session_id' => $sessionUuid,
            ])
            ->assertStatus(409);
    }
}
