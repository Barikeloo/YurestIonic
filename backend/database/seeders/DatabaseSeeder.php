<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminSeeder::class,
            RestaurantSeeder::class,
            UserSeeder::class,
            TaxSeeder::class,
            FamilySeeder::class,
            ZoneSeeder::class,
            ProductSeeder::class,
            DiningTableSeeder::class,
            OrderSeeder::class,
            OrderLineSeeder::class,
            SaleSeeder::class,
            UserQuickAccessSeeder::class,
            SuperAdminTestSeeder::class,
        ]);
    }
}
