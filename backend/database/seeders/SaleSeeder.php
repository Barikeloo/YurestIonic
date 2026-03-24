<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleSeeder extends Seeder
{
    public function run(): void
    {
        $tableIds = DB::table('tables')->pluck('id', 'name');
        $userIds = DB::table('users')->pluck('id', 'email');
        $productRows = DB::table('products')->select('id', 'name', 'price')->get()->keyBy('name');
        $taxPercentages = DB::table('taxes')->pluck('percentage', 'id');

        if (!$tableIds->has('S1') || !$tableIds->has('T1')) {
            return;
        }

        if (!$userIds->has('ana@tpv.local') || !$userIds->has('luis@tpv.local')) {
            return;
        }

        if (!$productRows->has('Cafe') || !$productRows->has('Bocadillo') || !$productRows->has('Cerveza')) {
            return;
        }

        $now = now();

        $closedSaleUuid = (string) Str::uuid();
        $closedOpenedAt = $now->copy()->subHours(2);
        $closedClosedAt = $now->copy()->subHour();

        $closedSaleId = DB::table('sales')->insertGetId([
            'uuid' => $closedSaleUuid,
            'ticket_number' => 1,
            'status' => 'closed',
            'table_id' => $tableIds['S1'],
            'opened_by_user_id' => $userIds['ana@tpv.local'],
            'closed_by_user_id' => $userIds['luis@tpv.local'],
            'diners' => 2,
            'opened_at' => $closedOpenedAt,
            'closed_at' => $closedClosedAt,
            'total' => 935,
            'created_at' => $closedOpenedAt,
            'updated_at' => $closedClosedAt,
            'deleted_at' => null,
        ]);

        $openSaleUuid = (string) Str::uuid();
        $openOpenedAt = $now->copy()->subMinutes(20);

        $openSaleId = DB::table('sales')->insertGetId([
            'uuid' => $openSaleUuid,
            'ticket_number' => null,
            'status' => 'open',
            'table_id' => $tableIds['T1'],
            'opened_by_user_id' => $userIds['ana@tpv.local'],
            'closed_by_user_id' => null,
            'diners' => 4,
            'opened_at' => $openOpenedAt,
            'closed_at' => null,
            'total' => 0,
            'created_at' => $openOpenedAt,
            'updated_at' => $openOpenedAt,
            'deleted_at' => null,
        ]);

        $cafe = $productRows['Cafe'];
        $bocadillo = $productRows['Bocadillo'];
        $cerveza = $productRows['Cerveza'];

        $cafeTax = (int) $taxPercentages[
            DB::table('products')->where('id', $cafe->id)->value('tax_id')
        ];

        $bocadilloTax = (int) $taxPercentages[
            DB::table('products')->where('id', $bocadillo->id)->value('tax_id')
        ];

        $cervezaTax = (int) $taxPercentages[
            DB::table('products')->where('id', $cerveza->id)->value('tax_id')
        ];

        DB::table('sales_lines')->insert([
            [
                'uuid' => (string) Str::uuid(),
                'sale_id' => $closedSaleId,
                'product_id' => $cafe->id,
                'user_id' => $userIds['ana@tpv.local'],
                'quantity' => 2,
                'price' => (int) $cafe->price,
                'tax_percentage' => $cafeTax,
                'created_at' => $closedOpenedAt,
                'updated_at' => $closedOpenedAt,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'sale_id' => $closedSaleId,
                'product_id' => $bocadillo->id,
                'user_id' => $userIds['ana@tpv.local'],
                'quantity' => 1,
                'price' => (int) $bocadillo->price,
                'tax_percentage' => $bocadilloTax,
                'created_at' => $closedOpenedAt,
                'updated_at' => $closedOpenedAt,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'sale_id' => $openSaleId,
                'product_id' => $cerveza->id,
                'user_id' => $userIds['ana@tpv.local'],
                'quantity' => 3,
                'price' => (int) $cerveza->price,
                'tax_percentage' => $cervezaTax,
                'created_at' => $openOpenedAt,
                'updated_at' => $openOpenedAt,
                'deleted_at' => null,
            ],
        ]);
    }
}
