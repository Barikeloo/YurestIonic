<?php

namespace Tests\Feature\Reporting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contract tests for the finanzas dashboard read endpoints (one per tab).
 * Each endpoint: rejects anonymous access, validates the period param, and
 * returns the expected JSON shape. The Resumen/heatmap/daily happy-paths rely
 * on MySQL-only SQL (HOUR/DAYOFWEEK), so their data path is covered by unit
 * tests + e2e instead of a SQLite feature happy-path.
 */
final class FinanzasDashboardReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_report_endpoint_requires_an_admin_session(): void
    {
        $endpoints = [
            '/api/admin/reports/summary?period=today',
            '/api/admin/reports/heatmap',
            '/api/admin/reports/products?period=today',
            '/api/admin/reports/employees?period=today',
            '/api/admin/reports/taxes?period=today',
            '/api/admin/reports/daily/pdf?period=today',
            '/api/admin/reports/products/pdf?period=today',
            '/api/admin/reports/families/pdf?period=today',
            '/api/admin/reports/tips/pdf?period=today',
            '/api/admin/reports/cash/pdf?period=today',
            '/api/admin/reports/taxes/pdf?period=today',
        ];

        foreach ($endpoints as $url) {
            $this->getJson($url)->assertStatus(401, "anonymous access to {$url} must be rejected");
        }
    }

    // ── Resumen (summary) ──────────────────────────────────────────────────

    public function test_summary_validates_period(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/summary?period=decade')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    // ── Productos ──────────────────────────────────────────────────────────

    public function test_products_validates_period(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/products?period=nope')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    public function test_products_returns_expected_shape(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/products?period=today')
            ->assertStatus(200)
            ->assertJsonStructure([
                'period_revenue', 'items', 'stock_critical', 'alert_count', 'by_zone', 'period_label', 'restaurant',
            ]);
    }

    // ── Empleados ──────────────────────────────────────────────────────────

    public function test_employees_validates_period(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/employees?period=nope')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    public function test_employees_returns_expected_shape(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/employees?period=week')
            ->assertStatus(200)
            ->assertJsonStructure(['items', 'period_label', 'restaurant']);
    }

    // ── Impuestos ──────────────────────────────────────────────────────────

    public function test_taxes_validates_quarter(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/taxes?period=today&quarter=T9')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['quarter']);
    }

    public function test_taxes_returns_data_for_valid_quarter(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/taxes?period=month&quarter=T1')
            ->assertStatus(200);
    }
}
