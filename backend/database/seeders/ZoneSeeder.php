<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('zones')->upsert([
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Salon',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Terraza',
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['name'], ['updated_at', 'deleted_at']);
    }
}
