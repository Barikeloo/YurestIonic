<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FamilySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('families')->upsert([
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Bebidas',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Comida',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Postres',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['name'], ['active', 'updated_at', 'deleted_at']);
    }
}
