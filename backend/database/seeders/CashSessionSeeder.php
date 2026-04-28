<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CashSessionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $restaurant = DB::table('restaurants')->first();
        $user = DB::table('users')->first();

        if (! $restaurant || ! $user) {
            return;
        }

        DB::table('cash_sessions')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'restaurant_id' => $restaurant->id,
                'device_id' => 'test-device-001',
                'opened_by_user_id' => $user->id,
                'closed_by_user_id' => null,
                'opened_at' => $now->copy()->subHours(8),
                'closed_at' => null,
                'initial_amount_cents' => 50000, // 500.00€
                'final_amount_cents' => null,
                'expected_amount_cents' => null,
                'discrepancy_cents' => null,
                'discrepancy_reason' => null,
                'z_report_number' => null,
                'z_report_hash' => null,
                'notes' => 'Turno mañana',
                'status' => 'open',
                'created_at' => $now->copy()->subHours(8),
                'updated_at' => $now->copy()->subHours(8),
            ],
            [
                'uuid' => (string) Str::uuid(),
                'restaurant_id' => $restaurant->id,
                'device_id' => 'test-device-002',
                'opened_by_user_id' => $user->id,
                'closed_by_user_id' => $user->id,
                'opened_at' => $now->copy()->subDays(1)->subHours(4),
                'closed_at' => $now->copy()->subDays(1)->subHours(2),
                'initial_amount_cents' => 30000, // 300.00€
                'final_amount_cents' => 28500, // 285.00€
                'expected_amount_cents' => 28000, // 280.00€
                'discrepancy_cents' => 500, // 5.00€ sobrante
                'discrepancy_reason' => 'Propina no declarada',
                'z_report_number' => 1,
                'z_report_hash' => hash('sha256', 'Z-1'),
                'notes' => 'Turno ayer',
                'status' => 'closed',
                'created_at' => $now->copy()->subDays(1)->subHours(4),
                'updated_at' => $now->copy()->subDays(1)->subHours(2),
            ],
        ]);
    }
}
