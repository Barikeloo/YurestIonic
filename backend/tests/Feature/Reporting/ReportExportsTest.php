<?php

namespace Tests\Feature\Reporting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Export history (Informes › Predefinidos): generate a CSV, see it recorded in
 * the history list, and download it back.
 */
final class ReportExportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_endpoints_require_an_admin_session(): void
    {
        $this->getJson('/api/admin/reports/export/products?period=today')->assertStatus(401);
        $this->getJson('/api/admin/reports/exports')->assertStatus(401);
        $this->getJson('/api/admin/reports/exports/' . Str::uuid() . '/download')->assertStatus(401);
    }

    public function test_export_rejects_invalid_type(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/export/bogus?period=today')
            ->assertStatus(422);
    }

    public function test_export_rejects_invalid_period(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/export/products?period=decade')
            ->assertStatus(422);
    }

    public function test_export_list_is_empty_initially(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/exports')
            ->assertStatus(200)
            ->assertJsonStructure(['items'])
            ->assertJsonCount(0, 'items');
    }

    public function test_export_is_recorded_listed_and_downloadable(): void
    {
        $tenant = $this->createTenantSession('admin');

        // 1. Generate a CSV export.
        $export = $this->withSession($tenant['session'])
            ->get('/api/admin/reports/export/products?period=today')
            ->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $export->headers->get('content-type'));

        // 2. It now appears in the history with the expected shape.
        $list = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/exports')
            ->assertStatus(200)
            ->assertJsonStructure(['items' => ['*' => [
                'uuid', 'title', 'report_type', 'format', 'filename', 'size_bytes', 'user_name', 'created_at',
            ]]]);

        $this->assertCount(1, $list->json('items'));
        $this->assertSame('CSV', $list->json('items.0.format'));
        $this->assertSame('products', $list->json('items.0.report_type'));

        // 3. The recorded export downloads back as CSV.
        $uuid = $list->json('items.0.uuid');

        $download = $this->withSession($tenant['session'])
            ->get("/api/admin/reports/exports/{$uuid}/download")
            ->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $download->headers->get('content-type'));
    }

    public function test_download_returns_404_for_unknown_uuid(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/exports/' . Str::uuid() . '/download')
            ->assertStatus(404);
    }
}
