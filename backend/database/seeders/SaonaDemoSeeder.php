<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo seeder para el restaurante SAONA.
 * Idempotente: se puede ejecutar varias veces sin duplicar datos.
 *
 * Ejecutar con:
 *   php artisan db:seed --class=SaonaDemoSeeder
 */
class SaonaDemoSeeder extends Seeder
{
    private const RESTAURANT_EMAIL = 'saona@gmail.com';
    private const DEMO_DEVICE_ID = 'seed-device-001';

    public function run(): void
    {
        $now = now();

        $restaurantId = $this->seedRestaurant($now);
        $this->wipeExistingData($restaurantId);
        $this->seedTaxes($restaurantId, $now);
        $userIds = $this->seedUsers($restaurantId, $now);
        $familyIds = $this->seedFamilies($restaurantId, $now);
        $this->seedProducts($restaurantId, $familyIds, $now);
        $zoneIds = $this->seedZones($restaurantId, $now);
        $this->seedTables($restaurantId, $zoneIds, $now);
        $this->seedQuickAccess($restaurantId, $userIds, $now);

        $this->command->info('SAONA demo seeded correctamente.');
        $this->command->info('  Admin:      '.self::RESTAURANT_EMAIL.' / 12345678 / PIN 1234');
        $this->command->info('  Supervisor: maria@saona.com / 12345678 / PIN 2345');
        $this->command->info('  Operadores: carlos/laura/javier/sofia@saona.com / 12345678 / PIN 3456-6789');
    }

    /**
     * Borra todos los datos operativos del restaurante SAONA para dejar la
     * demo en un estado conocido. Se respeta el orden de FKs.
     */
    private function wipeExistingData(int $restaurantId): void
    {
        // 1. Órdenes y ventas (borra en cascada order_lines, sales_lines, sale_payments)
        $orderIds = DB::table('orders')->where('restaurant_id', $restaurantId)->pluck('id');
        $saleIds = DB::table('sales')->where('restaurant_id', $restaurantId)->pluck('id');

        if ($saleIds->isNotEmpty()) {
            DB::table('sale_payments')->whereIn('sale_id', $saleIds)->delete();
            DB::table('tips')->whereIn('sale_id', $saleIds)->delete();
            DB::table('sales_lines')->whereIn('sale_id', $saleIds)->delete();
            DB::table('sales')->whereIn('id', $saleIds)->delete();
        }
        if ($orderIds->isNotEmpty()) {
            DB::table('order_lines')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->whereIn('id', $orderIds)->delete();
        }

        // 2. Cajas (z_reports primero, luego movements, luego sessions)
        $cashSessionIds = DB::table('cash_sessions')->where('restaurant_id', $restaurantId)->pluck('id');
        if ($cashSessionIds->isNotEmpty()) {
            DB::table('z_reports')->whereIn('cash_session_id', $cashSessionIds)->delete();
            DB::table('cash_movements')->whereIn('cash_session_id', $cashSessionIds)->delete();
            DB::table('cash_sessions')->whereIn('id', $cashSessionIds)->delete();
        }

        // 3. Logs de auditoría del restaurante (si la tabla existe y está ligada)
        if (DB::getSchemaBuilder()->hasTable('audit_logs')
            && DB::getSchemaBuilder()->hasColumn('audit_logs', 'restaurant_id')) {
            DB::table('audit_logs')->where('restaurant_id', $restaurantId)->delete();
        }

        // 4. Quick access y catálogo
        DB::table('user_quick_accesses')->where('restaurant_id', $restaurantId)->delete();
        DB::table('tables')->where('restaurant_id', $restaurantId)->delete();
        DB::table('zones')->where('restaurant_id', $restaurantId)->delete();
        DB::table('products')->where('restaurant_id', $restaurantId)->delete();
        DB::table('families')->where('restaurant_id', $restaurantId)->delete();
        DB::table('taxes')->where('restaurant_id', $restaurantId)->delete();
        DB::table('users')->where('restaurant_id', $restaurantId)->delete();
    }

    private function seedRestaurant($now): int
    {
        DB::table('restaurants')->updateOrInsert(
            ['email' => self::RESTAURANT_EMAIL],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'SAONA',
                'legal_name' => 'SAONA Mediterranean Food S.L.',
                'tax_id' => 'B98765432',
                'password' => Hash::make('12345678'),
                'updated_at' => $now,
                'created_at' => $now,
                'deleted_at' => null,
            ],
        );

        return (int) DB::table('restaurants')->where('email', self::RESTAURANT_EMAIL)->value('id');
    }

    private function seedTaxes(int $restaurantId, $now): void
    {
        $taxes = [
            ['name' => 'IVA Superreducido', 'percentage' => 4],
            ['name' => 'IVA Reducido',      'percentage' => 10],
            ['name' => 'IVA General',       'percentage' => 21],
        ];

        foreach ($taxes as $tax) {
            DB::table('taxes')->updateOrInsert(
                ['restaurant_id' => $restaurantId, 'name' => $tax['name']],
                [
                    'uuid' => (string) Str::uuid(),
                    'percentage' => $tax['percentage'],
                    'updated_at' => $now,
                    'created_at' => $now,
                    'deleted_at' => null,
                ],
            );
        }
    }

    /** @return array<string,int> email => user_id */
    private function seedUsers(int $restaurantId, $now): array
    {
        $password = Hash::make('12345678');

        $users = [
            ['email' => self::RESTAURANT_EMAIL, 'name' => 'José María Castellar', 'role' => 'admin',       'pin' => '1234'],
            ['email' => 'maria@saona.com',      'name' => 'María García',          'role' => 'supervisor', 'pin' => '2345'],
            ['email' => 'carlos@saona.com',     'name' => 'Carlos Ruiz',           'role' => 'operator',  'pin' => '3456'],
            ['email' => 'laura@saona.com',      'name' => 'Laura Martínez',        'role' => 'operator',  'pin' => '4567'],
            ['email' => 'javier@saona.com',     'name' => 'Javier López',          'role' => 'operator',  'pin' => '5678'],
            ['email' => 'sofia@saona.com',      'name' => 'Sofía Romero',          'role' => 'operator',  'pin' => '6789'],
        ];

        $ids = [];
        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                [
                    'restaurant_id' => $restaurantId,
                    'uuid' => (string) Str::uuid(),
                    'role' => $user['role'],
                    'image_src' => null,
                    'name' => $user['name'],
                    'password' => $password,
                    'pin' => Hash::make($user['pin']),
                    'email_verified_at' => $now,
                    'remember_token' => null,
                    'updated_at' => $now,
                    'created_at' => $now,
                    'deleted_at' => null,
                ],
            );
            $ids[$user['email']] = (int) DB::table('users')->where('email', $user['email'])->value('id');
        }

        return $ids;
    }

    /** @return array<string,int> family_name => family_id */
    private function seedFamilies(int $restaurantId, $now): array
    {
        $families = [
            'Cafés y Desayunos',
            'Brunch',
            'Ensaladas',
            'Arroces y Pastas',
            'Carnes',
            'Pescados',
            'Bebidas Frías',
            'Vinos y Cervezas',
            'Postres',
        ];

        $ids = [];
        foreach ($families as $name) {
            DB::table('families')->updateOrInsert(
                ['restaurant_id' => $restaurantId, 'name' => $name],
                [
                    'uuid' => (string) Str::uuid(),
                    'active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                    'deleted_at' => null,
                ],
            );
            $ids[$name] = (int) DB::table('families')
                ->where('restaurant_id', $restaurantId)
                ->where('name', $name)
                ->value('id');
        }

        return $ids;
    }

    /**
     * @param array<string,int> $familyIds family name => id
     */
    private function seedProducts(int $restaurantId, array $familyIds, $now): void
    {
        $taxIds = DB::table('taxes')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id', 'name');

        $ivaGeneral  = (int) $taxIds['IVA General'];
        $ivaReducido = (int) $taxIds['IVA Reducido'];

        // price en céntimos
        $catalog = [
            // ── Cafés y Desayunos ──
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café solo',                 'price' => 160,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café cortado',              'price' => 180,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café con leche',            'price' => 210,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Cappuccino',                'price' => 280,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Latte macchiato',           'price' => 320,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café helado',               'price' => 340,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Chocolate caliente',        'price' => 380,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Té o infusión',             'price' => 260,  'stock' => 999],

            // ── Brunch ──
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tostada de aguacate',         'price' => 950,  'stock' => 60],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tostada de salmón',           'price' => 1290, 'stock' => 40],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Huevos benedictinos',         'price' => 1190, 'stock' => 40],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Pancakes con frutos rojos',   'price' => 1050, 'stock' => 50],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Açaí bowl',                   'price' => 1150, 'stock' => 35],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tortilla francesa',           'price' => 850,  'stock' => 80],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Croissant jamón y queso',     'price' => 780,  'stock' => 50],

            // ── Ensaladas ──
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada César',           'price' => 1190, 'stock' => 50],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada Saona',           'price' => 1250, 'stock' => 50],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada de quinoa',       'price' => 1150, 'stock' => 40],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada de burrata',      'price' => 1350, 'stock' => 30],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada de pollo crispy', 'price' => 1290, 'stock' => 40],

            // ── Arroces y Pastas ──
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Paella mixta',           'price' => 1450, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Paella de verduras',     'price' => 1350, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Arroz del senyoret',     'price' => 1550, 'stock' => 25],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Risotto de boletus',     'price' => 1490, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Carbonara',              'price' => 1250, 'stock' => 40],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Tallarines al pesto',    'price' => 1190, 'stock' => 40],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Lasaña de carne',        'price' => 1290, 'stock' => 30],

            // ── Carnes ──
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Entrecot a la brasa',            'price' => 2250, 'stock' => 20],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Solomillo de ternera',          'price' => 2400, 'stock' => 15],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Hamburguesa Saona',             'price' => 1390, 'stock' => 40],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Pollo teriyaki',                'price' => 1450, 'stock' => 35],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Costillas a la barbacoa',       'price' => 1690, 'stock' => 25],

            // ── Pescados ──
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Salmón a la plancha',        'price' => 1750, 'stock' => 25],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Merluza al horno',           'price' => 1690, 'stock' => 25],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Bacalao confitado',          'price' => 1790, 'stock' => 20],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Atún rojo en tataki',        'price' => 1990, 'stock' => 20],

            // ── Bebidas Frías ──
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Agua mineral 50cl',      'price' => 200,  'stock' => 200],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Refresco (Coca-Cola)',   'price' => 280,  'stock' => 200],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Refresco (Fanta)',       'price' => 280,  'stock' => 150],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Zumo natural de naranja','price' => 380,  'stock' => 80],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Smoothie de frutos rojos','price' => 450,  'stock' => 50],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Limonada casera',        'price' => 350,  'stock' => 70],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Tónica premium',         'price' => 320,  'stock' => 80],

            // ── Vinos y Cervezas ──
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Caña',                'price' => 200,  'stock' => 500],
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Cerveza 33cl',        'price' => 280,  'stock' => 300],
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Cerveza sin alcohol', 'price' => 280,  'stock' => 100],
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Copa vino tinto',     'price' => 380,  'stock' => 200],
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Copa vino blanco',    'price' => 380,  'stock' => 200],
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Copa cava',           'price' => 420,  'stock' => 100],
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Botella vino tinto',  'price' => 1890, 'stock' => 40],
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Cóctel de la casa',   'price' => 750,  'stock' => 60],
            ['fam' => 'Vinos y Cervezas', 'tax' => $ivaGeneral, 'name' => 'Gin tonic',           'price' => 800,  'stock' => 80],

            // ── Postres ──
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Tarta de zanahoria',          'price' => 690,  'stock' => 30],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Coulant de chocolate',        'price' => 650,  'stock' => 30],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Cheesecake',                  'price' => 690,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Tiramisú',                    'price' => 650,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Brownie con helado',          'price' => 600,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Sorbete de limón',            'price' => 480,  'stock' => 40],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Crema catalana',              'price' => 550,  'stock' => 25],
        ];

        foreach ($catalog as $item) {
            DB::table('products')->updateOrInsert(
                ['restaurant_id' => $restaurantId, 'name' => $item['name']],
                [
                    'uuid' => (string) Str::uuid(),
                    'family_id' => $familyIds[$item['fam']],
                    'tax_id' => $item['tax'],
                    'image_src' => null,
                    'price' => $item['price'],
                    'stock' => $item['stock'],
                    'active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                    'deleted_at' => null,
                ],
            );
        }
    }

    /** @return array<string,int> zone_name => zone_id */
    private function seedZones(int $restaurantId, $now): array
    {
        $zones = ['Terraza', 'Salón Principal', 'Barra', 'Reservado'];

        $ids = [];
        foreach ($zones as $name) {
            DB::table('zones')->updateOrInsert(
                ['restaurant_id' => $restaurantId, 'name' => $name],
                [
                    'uuid' => (string) Str::uuid(),
                    'updated_at' => $now,
                    'created_at' => $now,
                    'deleted_at' => null,
                ],
            );
            $ids[$name] = (int) DB::table('zones')
                ->where('restaurant_id', $restaurantId)
                ->where('name', $name)
                ->value('id');
        }

        return $ids;
    }

    /**
     * @param array<string,int> $zoneIds zone name => id
     */
    private function seedTables(int $restaurantId, array $zoneIds, $now): void
    {
        $layout = [
            'Terraza'         => ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8'],
            'Salón Principal' => ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10', 'S11', 'S12'],
            'Barra'           => ['B1', 'B2', 'B3', 'B4', 'B5', 'B6'],
            'Reservado'       => ['R1', 'R2'],
        ];

        foreach ($layout as $zoneName => $tables) {
            foreach ($tables as $tableName) {
                DB::table('tables')->updateOrInsert(
                    ['restaurant_id' => $restaurantId, 'name' => $tableName],
                    [
                        'uuid' => (string) Str::uuid(),
                        'zone_id' => $zoneIds[$zoneName],
                        'updated_at' => $now,
                        'created_at' => $now,
                        'deleted_at' => null,
                    ],
                );
            }
        }
    }

    /**
     * @param array<string,int> $userIds email => id
     */
    private function seedQuickAccess(int $restaurantId, array $userIds, $now): void
    {
        $i = 0;
        foreach ($userIds as $userId) {
            DB::table('user_quick_accesses')->updateOrInsert(
                [
                    'restaurant_id' => $restaurantId,
                    'user_id' => $userId,
                    'device_id' => self::DEMO_DEVICE_ID,
                ],
                [
                    'last_login_at' => $now->copy()->subMinutes($i),
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
            $i++;
        }
    }
}
