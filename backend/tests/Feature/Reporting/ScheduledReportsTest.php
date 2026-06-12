<?php

namespace Tests\Feature\Reporting;

use App\Reporting\Application\Shared\DateRange;
use App\Reporting\Application\Shared\ReportFileGeneratorInterface;
use App\Mail\ScheduledReportMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ScheduledReportsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $overrides
     */
    private function seedReport(array $tenant, array $overrides = []): string
    {
        $uuid = (string) Str::uuid();

        DB::table('scheduled_reports')->insert(array_merge([
            'uuid'          => $uuid,
            'restaurant_id' => $tenant['restaurant_id'],
            'report_type'   => 'daily',
            'format'        => 'PDF',
            'frequency'     => 'daily',
            'time'          => '08:00',
            'weekday'       => null,
            'day_of_month'  => null,
            'recipients'    => json_encode(['admin@test.com']),
            'name'          => 'Daily summary',
            'active'        => true,
            'next_run_at'   => now()->addDay()->format('Y-m-d H:i:s'),
            'created_at'    => now(),
            'updated_at'    => now(),
        ], $overrides));

        return $uuid;
    }

    public function test_requires_admin_session(): void
    {
        $this->getJson('/api/admin/reports/scheduled')->assertStatus(401);
    }

    public function test_lists_scheduled_reports_for_restaurant(): void
    {
        $tenant = $this->createTenantSession('admin');
        $uuid = $this->seedReport($tenant);

        $response = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/scheduled')
            ->assertStatus(200);

        $response->assertJsonStructure([
            '*' => ['uuid', 'report_type', 'format', 'frequency', 'time', 'recipients', 'name', 'active', 'next_run_at'],
        ]);

        $this->assertCount(1, $response->json());
        $this->assertSame($uuid, $response->json('0.uuid'));
        $this->assertSame(['admin@test.com'], $response->json('0.recipients'));
        $this->assertTrue($response->json('0.active'));
    }

    public function test_does_not_list_reports_from_other_restaurants(): void
    {
        $other = $this->createTenantSession('admin');
        $this->seedReport($other);

        $tenant = $this->createTenantSession('admin');

        $response = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/scheduled')
            ->assertStatus(200);

        $this->assertCount(0, $response->json());
    }

    public function test_creates_scheduled_report(): void
    {
        $tenant = $this->createTenantSession('admin');

        $response = $this->withSession($tenant['session'])
            ->postJson('/api/admin/reports/scheduled', [
                'report_type' => 'products',
                'format'      => 'CSV',
                'frequency'   => 'weekly',
                'time'        => '10:30',
                'weekday'     => 1,
                'recipients'  => ['a@b.com', 'c@d.com'],
                'name'        => 'Weekly products',
                'active'      => true,
            ])
            ->assertStatus(201);

        $uuid = $response->json('uuid');
        $this->assertNotEmpty($uuid);

        $this->assertDatabaseHas('scheduled_reports', [
            'uuid'          => $uuid,
            'restaurant_id' => $tenant['restaurant_id'],
            'report_type'   => 'products',
            'format'        => 'CSV',
            'frequency'     => 'weekly',
            'weekday'       => 1,
            'name'          => 'Weekly products',
        ]);
    }

    public function test_create_validates_payload(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/reports/scheduled', [
                'report_type' => 'invalid',
                'format'      => 'DOCX',
                'frequency'   => 'yearly',
                'time'        => '99:99',
                'recipients'  => [],
                'name'        => '',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['report_type', 'format', 'frequency', 'time', 'recipients', 'name']);
    }

    public function test_create_rejects_invalid_recipient_email(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->postJson('/api/admin/reports/scheduled', [
                'report_type' => 'daily',
                'format'      => 'PDF',
                'frequency'   => 'daily',
                'time'        => '08:00',
                'recipients'  => ['not-an-email'],
                'name'        => 'Daily',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['recipients.0']);
    }

    public function test_updates_scheduled_report(): void
    {
        $tenant = $this->createTenantSession('admin');
        $uuid = $this->seedReport($tenant);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/reports/scheduled/{$uuid}", [
                'report_type' => 'families',
                'format'      => 'CSV',
                'frequency'   => 'monthly',
                'time'        => '09:15',
                'day_of_month'=> 5,
                'recipients'  => ['new@test.com'],
                'name'        => 'Monthly families',
                'active'      => true,
            ])
            ->assertStatus(204);

        $this->assertDatabaseHas('scheduled_reports', [
            'uuid'         => $uuid,
            'report_type'  => 'families',
            'format'       => 'CSV',
            'frequency'    => 'monthly',
            'day_of_month' => 5,
            'name'         => 'Monthly families',
        ]);
    }

    public function test_update_returns_404_for_unknown_uuid(): void
    {
        $tenant = $this->createTenantSession('admin');
        $uuid = (string) Str::uuid();

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/reports/scheduled/{$uuid}", [
                'report_type' => 'daily',
                'format'      => 'PDF',
                'frequency'   => 'daily',
                'time'        => '08:00',
                'recipients'  => ['a@b.com'],
                'name'        => 'Daily',
                'active'      => true,
            ])
            ->assertStatus(404);
    }

    public function test_toggles_active_state(): void
    {
        $tenant = $this->createTenantSession('admin');
        $uuid = $this->seedReport($tenant, ['active' => true]);

        $this->withSession($tenant['session'])
            ->putJson("/api/admin/reports/scheduled/{$uuid}/toggle")
            ->assertStatus(200)
            ->assertJson(['uuid' => $uuid, 'active' => false]);

        $this->assertSame(0, (int) DB::table('scheduled_reports')->where('uuid', $uuid)->value('active'));
    }

    public function test_deletes_scheduled_report(): void
    {
        $tenant = $this->createTenantSession('admin');
        $uuid = $this->seedReport($tenant);

        $this->withSession($tenant['session'])
            ->deleteJson("/api/admin/reports/scheduled/{$uuid}")
            ->assertStatus(204);

        $this->assertNotNull(DB::table('scheduled_reports')->where('uuid', $uuid)->value('deleted_at'));
    }

    public function test_sends_report_now(): void
    {
        $tenant = $this->createTenantSession('admin');
        $uuid = $this->seedReport($tenant);

        Mail::fake();
        $this->swapReportFileGenerator();

        $this->withSession($tenant['session'])
            ->postJson("/api/admin/reports/scheduled/{$uuid}/send")
            ->assertStatus(200)
            ->assertJson(['uuid' => $uuid]);

        Mail::assertSent(ScheduledReportMail::class);
    }

    public function test_send_now_returns_404_for_unknown_uuid(): void
    {
        $tenant = $this->createTenantSession('admin');
        $uuid = (string) Str::uuid();

        Mail::fake();
        $this->swapReportFileGenerator();

        $this->withSession($tenant['session'])
            ->postJson("/api/admin/reports/scheduled/{$uuid}/send")
            ->assertStatus(404);

        Mail::assertNothingSent();
    }

    private function swapReportFileGenerator(): void
    {
        $this->app->instance(ReportFileGeneratorInterface::class, new class implements ReportFileGeneratorInterface {
            public function generate(int $restaurantId, string $type, string $format, DateRange $range, ?string $quarter = null, ?int $year = null): array
            {
                return ['filename' => 'report.csv', 'mimeType' => 'text/csv', 'contents' => 'col1,col2'];
            }
        });
    }
}
