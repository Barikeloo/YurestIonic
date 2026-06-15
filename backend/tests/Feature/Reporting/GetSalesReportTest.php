<?php

namespace Tests\Feature\Reporting;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

final class GetSalesReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_sales_list_paginated(): void
    {
        $tenant = $this->createTenantSession('admin');

        $saleUuid = (string) Str::uuid();
        $userId   = DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id');

        DB::table('sales')->insert([
            'uuid'             => $saleUuid,
            'restaurant_id'    => $tenant['restaurant_id'],
            'ticket_number'    => 1,
            'status'           => 'closed',
            'value_date'       => now()->subHours(2),
            'total'            => 5000,
            'opened_by_user_id' => $userId,
            'document_type'    => 'simplified',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $response = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/sales?period=today')
            ->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'uuid', 'ticket_number', 'value_date', 'total', 'status',
                    'zone_name', 'table_name', 'diners', 'opened_by',
                    'payment_methods', 'tips_total',
                ],
            ],
            'meta' => ['total', 'page', 'per_page', 'last_page'],
            'totals' => ['revenue', 'cash', 'card', 'bizum', 'other', 'tips'],
        ]);

        $this->assertCount(1, $response->json('data'));
        $this->assertSame(5000, $response->json('data.0.total'));
        $this->assertSame(5000, $response->json('totals.revenue'));
    }

    public function test_returns_empty_list_when_no_sales(): void
    {
        $tenant = $this->createTenantSession('admin');

        $response = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/sales?period=today')
            ->assertStatus(200);

        $this->assertCount(0, $response->json('data'));
        $this->assertSame(0, $response->json('totals.revenue'));
    }

    public function test_returns_totals_by_payment_method(): void
    {
        $tenant = $this->createTenantSession('admin');
        $userId = DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id');
        $saleId = DB::table('sales')->insertGetId([
            'uuid'             => (string) Str::uuid(),
            'restaurant_id'    => $tenant['restaurant_id'],
            'ticket_number'    => 2,
            'status'           => 'closed',
            'value_date'       => now()->subHour(),
            'total'            => 10000,
            'opened_by_user_id' => $userId,
            'document_type'    => 'simplified',
            'created_at'       => now(),
            'updated_at'       => now(),
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
            ['uuid' => (string) Str::uuid(), 'sale_id' => $saleId, 'restaurant_id' => $tenant['restaurant_id'], 'cash_session_id' => $cashId, 'method' => 'card', 'amount_cents' => 8000, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()],
            ['uuid' => (string) Str::uuid(), 'sale_id' => $saleId, 'restaurant_id' => $tenant['restaurant_id'], 'cash_session_id' => $cashId, 'method' => 'cash', 'amount_cents' => 2000, 'user_id' => $userId, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/sales?period=today')
            ->assertStatus(200);

        $this->assertSame(10000, $response->json('totals.revenue'));
        $this->assertSame(8000, $response->json('totals.card'));
        $this->assertSame(2000, $response->json('totals.cash'));
    }

    public function test_requires_admin_session(): void
    {
        $this->getJson('/api/admin/reports/sales?period=today')
            ->assertStatus(401);
    }

    public function test_validates_period_parameter(): void
    {
        $tenant = $this->createTenantSession('admin');

        $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/sales?period=invalid')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }

    public function test_filters_by_period(): void
    {
        $tenant = $this->createTenantSession('admin');
        $userId = DB::table('users')->where('uuid', $tenant['user_uuid'])->value('id');

        // A sale from yesterday: out of "today", inside "yesterday".
        DB::table('sales')->insert([
            'uuid'             => (string) Str::uuid(),
            'restaurant_id'    => $tenant['restaurant_id'],
            'ticket_number'    => 10,
            'status'           => 'closed',
            'value_date'       => now()->subDay(),
            'total'            => 3000,
            'opened_by_user_id' => $userId,
            'document_type'    => 'simplified',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $responseToday = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/sales?period=today')
            ->assertStatus(200);

        $responseYesterday = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/sales?period=yesterday')
            ->assertStatus(200);

        $this->assertCount(0, $responseToday->json('data'));
        $this->assertCount(1, $responseYesterday->json('data'));

        // A sale at "now" is always inside the current week and never in the
        // future, so the weekly window has at least one regardless of which
        // weekday the suite runs on (yesterday can fall in the previous week
        // on a Monday).
        DB::table('sales')->insert([
            'uuid'             => (string) Str::uuid(),
            'restaurant_id'    => $tenant['restaurant_id'],
            'ticket_number'    => 11,
            'status'           => 'closed',
            'value_date'       => now(),
            'total'            => 5000,
            'opened_by_user_id' => $userId,
            'document_type'    => 'simplified',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $responseWeek = $this->withSession($tenant['session'])
            ->getJson('/api/admin/reports/sales?period=week')
            ->assertStatus(200);

        $this->assertGreaterThanOrEqual(1, count($responseWeek->json('data')));
    }
}
