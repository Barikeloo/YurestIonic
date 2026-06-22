<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiningTableSeeder extends Seeder
{
    public function run(): void
    {
        $restaurantId = DB::table('restaurants')->first()?->id;
        $zoneIds = DB::table('zones')->pluck('id', 'name');

        if (! $restaurantId || ! $zoneIds->has('Salon') || ! $zoneIds->has('Terraza')) {
            return;
        }

        $now = now();

        $tables = [
            ['zone' => 'Salon',   'name' => 'S1'],
            ['zone' => 'Salon',   'name' => 'S2'],
            ['zone' => 'Terraza', 'name' => 'T1'],
            ['zone' => 'Terraza', 'name' => 'T2'],
        ];

        $hasQrTokens = DB::getSchemaBuilder()->hasTable('table_qr_tokens');

        foreach ($tables as $t) {
            DB::table('tables')->upsert([
                [
                    'restaurant_id' => $restaurantId,
                    'uuid'          => (string) Str::uuid(),
                    'zone_id'       => $zoneIds[$t['zone']],
                    'name'          => $t['name'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                    'deleted_at'    => null,
                ],
            ], ['name'], ['zone_id', 'updated_at', 'deleted_at']);

            if ($hasQrTokens) {
                $tableId = DB::table('tables')
                    ->where('restaurant_id', $restaurantId)
                    ->where('name', $t['name'])
                    ->value('id');

                DB::table('table_qr_tokens')->updateOrInsert(
                    ['table_id' => $tableId],
                    [
                        'uuid'            => (string) Str::uuid(),
                        'restaurant_id'   => $restaurantId,
                        'token'           => bin2hex(random_bytes(32)),
                        'catalog_version' => 1,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ],
                );
            }
        }
    }
}
