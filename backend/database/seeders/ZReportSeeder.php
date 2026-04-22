<?php

namespace Database\Seeders;

use App\Cash\Infrastructure\Persistence\Models\EloquentZReport;
use App\Cash\Infrastructure\Persistence\Models\EloquentCashSession;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ZReportSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = EloquentRestaurant::first();
        $cashSession = EloquentCashSession::first();

        if (!$restaurant || !$cashSession) {
            $this->command->warn('No restaurant or cash session found. Skipping ZReport seeder.');
            return;
        }

        $zReports = [
            [
                'uuid' => Str::uuid(),
                'restaurant_id' => $restaurant->id,
                'cash_session_id' => $cashSession->id,
                'report_number' => 1,
                'report_hash' => hash('sha256', 'test_report_1'),
                'total_sales_cents' => 15000,
                'total_cash_cents' => 10000,
                'total_card_cents' => 5000,
                'total_other_cents' => 0,
                'cash_in_cents' => 5000,
                'cash_out_cents' => 2000,
                'tips_cents' => 1000,
                'discrepancy_cents' => 0,
                'sales_count' => 15,
                'cancelled_sales_count' => 2,
                'generated_at' => now()->subDays(2),
            ],
            [
                'uuid' => Str::uuid(),
                'restaurant_id' => $restaurant->id,
                'cash_session_id' => $cashSession->id,
                'report_number' => 2,
                'report_hash' => hash('sha256', 'test_report_2'),
                'total_sales_cents' => 25000,
                'total_cash_cents' => 15000,
                'total_card_cents' => 8000,
                'total_other_cents' => 2000,
                'cash_in_cents' => 10000,
                'cash_out_cents' => 5000,
                'tips_cents' => 2000,
                'discrepancy_cents' => 500,
                'sales_count' => 25,
                'cancelled_sales_count' => 1,
                'generated_at' => now()->subDay(),
            ],
            [
                'uuid' => Str::uuid(),
                'restaurant_id' => $restaurant->id,
                'cash_session_id' => $cashSession->id,
                'report_number' => 3,
                'report_hash' => hash('sha256', 'test_report_3'),
                'total_sales_cents' => 32000,
                'total_cash_cents' => 20000,
                'total_card_cents' => 10000,
                'total_other_cents' => 2000,
                'cash_in_cents' => 15000,
                'cash_out_cents' => 8000,
                'tips_cents' => 3000,
                'discrepancy_cents' => -200,
                'sales_count' => 32,
                'cancelled_sales_count' => 3,
                'generated_at' => now(),
            ],
        ];

        foreach ($zReports as $zReport) {
            EloquentZReport::create($zReport);
        }

        $this->command->info('Z-Reports seeded successfully.');
    }
}
