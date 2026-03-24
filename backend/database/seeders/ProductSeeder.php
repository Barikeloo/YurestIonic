<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $familyIds = DB::table('families')->pluck('id', 'name');
        $taxIds = DB::table('taxes')->pluck('id', 'name');

        if (!$familyIds->has('Bebidas') || !$familyIds->has('Comida') || !$familyIds->has('Postres')) {
            return;
        }

        if (!$taxIds->has('IVA Reducido') || !$taxIds->has('IVA General')) {
            return;
        }

        $now = now();

        DB::table('products')->upsert([
            [
                'uuid' => (string) Str::uuid(),
                'family_id' => $familyIds['Bebidas'],
                'tax_id' => $taxIds['IVA General'],
                'image_src' => null,
                'name' => 'Cafe',
                'price' => 150,
                'stock' => 100,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'family_id' => $familyIds['Bebidas'],
                'tax_id' => $taxIds['IVA General'],
                'image_src' => null,
                'name' => 'Cerveza',
                'price' => 250,
                'stock' => 80,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'family_id' => $familyIds['Comida'],
                'tax_id' => $taxIds['IVA Reducido'],
                'image_src' => null,
                'name' => 'Bocadillo',
                'price' => 550,
                'stock' => 50,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'family_id' => $familyIds['Postres'],
                'tax_id' => $taxIds['IVA Reducido'],
                'image_src' => null,
                'name' => 'Tarta de queso',
                'price' => 450,
                'stock' => 20,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'family_id' => $familyIds['Bebidas'],
                'tax_id' => $taxIds['IVA General'],
                'image_src' => null,
                'name' => 'Refresco descatalogado',
                'price' => 220,
                'stock' => 0,
                'active' => false,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ],
        ], ['name'], ['family_id', 'tax_id', 'price', 'stock', 'active', 'updated_at', 'deleted_at']);
    }
}
