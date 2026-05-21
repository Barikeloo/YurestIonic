<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo seeder para el restaurante Bar Manolo.
 * Idempotente: se puede ejecutar varias veces sin duplicar datos.
 *
 * Ejecutar con:
 *   php artisan db:seed --class=SaonaDemoSeeder
 */
class SaonaDemoSeeder extends Seeder
{
    private const RESTAURANT_EMAIL = 'barmanolo@gmail.com';

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
        $this->seedProductModifiers($restaurantId, $now);
        $this->seedProductVariants($restaurantId, $now);
        $zoneIds = $this->seedZones($restaurantId, $now);
        $this->seedTables($restaurantId, $zoneIds, $now);
        $this->seedQuickAccess($restaurantId, $userIds, $now);

        $this->command->info('Bar Manolo demo seeded correctamente.');
        $this->command->info('  Admin:      '.self::RESTAURANT_EMAIL.' / 12345678 / PIN 1234');
        $this->command->info('  Supervisor: maria@saona.com / 12345678 / PIN 2345');
        $this->command->info('  Operadores: carlos/laura/javier/sofia@saona.com / 12345678 / PIN 3456-6789');
    }

    /**
     * Borra todos los datos operativos del restaurante Bar Manolo para dejar la
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
                'name' => 'Bar Manolo',
                'legal_name' => 'Bar Manolo Restauración S.L.',
                'tax_id' => 'B12345678',
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
            ['email' => self::RESTAURANT_EMAIL, 'name' => 'Manolo Pérez',          'role' => 'admin',       'pin' => '1234'],
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
            'Entrantes y Tapas',
            'Ensaladas',
            'Arroces y Pastas',
            'Carnes',
            'Pescados',
            'Bebidas Frías',
            'Cervezas',
            'Vinos',
            'Cócteles y Destilados',
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
     * @param  array<string,int>  $familyIds  family name => id
     */
    private function seedProducts(int $restaurantId, array $familyIds, $now): void
    {
        $taxIds = DB::table('taxes')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id', 'name');

        $ivaGeneral = (int) $taxIds['IVA General'];
        $ivaReducido = (int) $taxIds['IVA Reducido'];

        // price en céntimos
        $catalog = [
            // ── Cafés y Desayunos ──
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café solo',                 'price' => 160,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café cortado',              'price' => 180,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café con leche',            'price' => 210,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café americano',            'price' => 220,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Espresso doble',            'price' => 220,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café bombón',               'price' => 240,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Leche manchada',            'price' => 200,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Carajillo',                 'price' => 280,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Cappuccino',                'price' => 280,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Latte macchiato',           'price' => 320,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Flat white',                'price' => 320,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Mocaccino',                 'price' => 360,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Café helado',               'price' => 340,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Matcha latte',              'price' => 380,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Chai latte',                'price' => 360,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Chocolate caliente',        'price' => 380,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Té o infusión',             'price' => 260,  'stock' => 999],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Tostada con tomate',        'price' => 320,  'stock' => 100],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Tostada con mantequilla y mermelada', 'price' => 280,  'stock' => 100],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Croissant a la plancha',    'price' => 250,  'stock' => 80],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Napolitana de chocolate',   'price' => 220,  'stock' => 60],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Magdalena casera',          'price' => 180,  'stock' => 80],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Churros con chocolate',     'price' => 480,  'stock' => 40],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Bocadillo de jamón',        'price' => 520,  'stock' => 60],
            ['fam' => 'Cafés y Desayunos', 'tax' => $ivaReducido, 'name' => 'Bocadillo de tortilla',     'price' => 480,  'stock' => 60],

            // ── Brunch ──
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tostada de aguacate',         'price' => 950,  'stock' => 60],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tostada de salmón',           'price' => 1290, 'stock' => 40],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tostada de hummus',           'price' => 890,  'stock' => 40],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Huevos benedictinos',         'price' => 1190, 'stock' => 40],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Huevos rotos con jamón',      'price' => 1290, 'stock' => 40],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Shakshuka',                   'price' => 1190, 'stock' => 30],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Pancakes con frutos rojos',   'price' => 1050, 'stock' => 50],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tortitas americanas',         'price' => 980,  'stock' => 40],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'French toast',                'price' => 1090, 'stock' => 35],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Açaí bowl',                   'price' => 1150, 'stock' => 35],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Yogur con granola',           'price' => 690,  'stock' => 50],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tortilla francesa',           'price' => 850,  'stock' => 80],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Tortilla de patatas',         'price' => 950,  'stock' => 60],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Croissant jamón y queso',     'price' => 780,  'stock' => 50],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Bagel salmón y queso crema',  'price' => 1190, 'stock' => 30],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Sandwich club',               'price' => 1190, 'stock' => 40],
            ['fam' => 'Brunch', 'tax' => $ivaReducido, 'name' => 'Sandwich vegetal',            'price' => 950,  'stock' => 40],

            // ── Entrantes y Tapas ──
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Jamón ibérico de bellota',    'price' => 2490, 'stock' => 30],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Tabla de quesos',             'price' => 1690, 'stock' => 30],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Tabla de embutidos ibéricos', 'price' => 1890, 'stock' => 30],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Croquetas caseras (6 ud.)',   'price' => 990,  'stock' => 60],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Patatas bravas',              'price' => 690,  'stock' => 100],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Patatas alioli',              'price' => 650,  'stock' => 100],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Pimientos de Padrón',         'price' => 790,  'stock' => 50],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Boquerones en vinagre',       'price' => 750,  'stock' => 40],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Anchoas del Cantábrico',      'price' => 1290, 'stock' => 25],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Pulpo a la gallega',          'price' => 1690, 'stock' => 30],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Calamares a la andaluza',     'price' => 1190, 'stock' => 50],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Gambas al ajillo',            'price' => 1290, 'stock' => 40],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Mejillones a la marinera',    'price' => 1090, 'stock' => 40],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Almejas a la marinera',       'price' => 1490, 'stock' => 30],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Tartar de atún',              'price' => 1690, 'stock' => 25],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Carpaccio de ternera',        'price' => 1390, 'stock' => 25],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Tomate, mozzarella y albahaca', 'price' => 1090, 'stock' => 40],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Hummus con pita',             'price' => 790,  'stock' => 50],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Guacamole con nachos',        'price' => 890,  'stock' => 50],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Nachos con queso',            'price' => 990,  'stock' => 50],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Alitas de pollo BBQ',         'price' => 990,  'stock' => 50],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Edamames con sal',            'price' => 690,  'stock' => 50],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Aceitunas marinadas',         'price' => 380,  'stock' => 100],
            ['fam' => 'Entrantes y Tapas', 'tax' => $ivaReducido, 'name' => 'Pan con tomate',              'price' => 320,  'stock' => 200],

            // ── Ensaladas ──
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada César',           'price' => 1190, 'stock' => 50],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada de la Casa',     'price' => 1250, 'stock' => 50],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada de quinoa',       'price' => 1150, 'stock' => 40],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada de burrata',      'price' => 1350, 'stock' => 30],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada caprese',         'price' => 1190, 'stock' => 40],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada niçoise',         'price' => 1290, 'stock' => 35],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada Waldorf',         'price' => 1190, 'stock' => 30],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada de pollo crispy', 'price' => 1290, 'stock' => 40],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada templada de pulpo', 'price' => 1490, 'stock' => 25],
            ['fam' => 'Ensaladas', 'tax' => $ivaReducido, 'name' => 'Ensalada de queso de cabra y nueces', 'price' => 1290, 'stock' => 35],

            // ── Arroces y Pastas ──
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Paella mixta',           'price' => 1450, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Paella de verduras',     'price' => 1350, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Paella de marisco',      'price' => 1690, 'stock' => 25],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Arroz negro',            'price' => 1590, 'stock' => 25],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Arroz a banda',          'price' => 1490, 'stock' => 25],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Arroz del senyoret',     'price' => 1550, 'stock' => 25],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Fideuá de marisco',      'price' => 1590, 'stock' => 25],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Risotto de boletus',     'price' => 1490, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Risotto de gambas',      'price' => 1590, 'stock' => 25],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Carbonara',              'price' => 1250, 'stock' => 40],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Tallarines al pesto',    'price' => 1190, 'stock' => 40],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Espaguetis a la boloñesa', 'price' => 1190, 'stock' => 40],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Espaguetis aglio e olio', 'price' => 1090, 'stock' => 40],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Penne arrabbiata',       'price' => 1090, 'stock' => 40],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Ravioli de ricotta y espinacas', 'price' => 1290, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Gnocchi a los cuatro quesos', 'price' => 1290, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Lasaña de carne',        'price' => 1290, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Mac & cheese',           'price' => 1190, 'stock' => 30],
            ['fam' => 'Arroces y Pastas', 'tax' => $ivaReducido, 'name' => 'Canelones de la abuela', 'price' => 1290, 'stock' => 30],

            // ── Carnes ──
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Entrecot a la brasa',            'price' => 2250, 'stock' => 20],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Solomillo de ternera',           'price' => 2400, 'stock' => 15],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Chuletón de vaca madurada',      'price' => 3490, 'stock' => 12],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Secreto ibérico',                'price' => 1890, 'stock' => 20],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Presa ibérica',                  'price' => 1990, 'stock' => 18],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Magret de pato',                 'price' => 2190, 'stock' => 15],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Cordero asado',                  'price' => 2490, 'stock' => 12],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Hamburguesa Manolo',             'price' => 1390, 'stock' => 40],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Hamburguesa vegetariana',        'price' => 1290, 'stock' => 30],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Hamburguesa de pollo crispy',    'price' => 1390, 'stock' => 35],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Pollo teriyaki',                 'price' => 1450, 'stock' => 35],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Pollo al curry',                 'price' => 1390, 'stock' => 30],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Costillas a la barbacoa',        'price' => 1690, 'stock' => 25],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Tacos de carrillera',            'price' => 1390, 'stock' => 30],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Fajitas de pollo',               'price' => 1490, 'stock' => 30],
            ['fam' => 'Carnes', 'tax' => $ivaReducido, 'name' => 'Solomillo Wellington',           'price' => 2690, 'stock' => 12],

            // ── Pescados ──
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Salmón a la plancha',        'price' => 1750, 'stock' => 25],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Merluza al horno',           'price' => 1690, 'stock' => 25],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Bacalao confitado',          'price' => 1790, 'stock' => 20],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Atún rojo en tataki',        'price' => 1990, 'stock' => 20],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Lubina a la sal',            'price' => 1890, 'stock' => 18],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Dorada a la espalda',        'price' => 1790, 'stock' => 20],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Rodaballo a la plancha',     'price' => 2290, 'stock' => 15],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Rape en salsa verde',        'price' => 1990, 'stock' => 15],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Sepia a la plancha',         'price' => 1590, 'stock' => 25],
            ['fam' => 'Pescados', 'tax' => $ivaReducido, 'name' => 'Gambones a la plancha',      'price' => 1990, 'stock' => 25],

            // ── Bebidas Frías ──
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Agua mineral 50cl',          'price' => 200,  'stock' => 200],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Agua mineral 1L',            'price' => 300,  'stock' => 150],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Agua con gas 50cl',          'price' => 250,  'stock' => 120],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Refresco (Coca-Cola)',       'price' => 280,  'stock' => 200],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Refresco (Coca-Cola Zero)',  'price' => 280,  'stock' => 200],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Refresco (Fanta)',           'price' => 280,  'stock' => 150],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Refresco (Sprite)',          'price' => 280,  'stock' => 120],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Refresco (Nestea)',          'price' => 290,  'stock' => 100],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Aquarius',                   'price' => 290,  'stock' => 100],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Bitter Kas',                 'price' => 290,  'stock' => 80],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Tónica Schweppes',           'price' => 320,  'stock' => 150],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Tónica premium',             'price' => 380,  'stock' => 80],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Red Bull',                   'price' => 380,  'stock' => 80],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Zumo natural de naranja',    'price' => 380,  'stock' => 80],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Zumo de tomate',             'price' => 320,  'stock' => 60],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Zumo de piña',               'price' => 320,  'stock' => 60],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Smoothie de frutos rojos',   'price' => 450,  'stock' => 50],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Smoothie verde detox',       'price' => 450,  'stock' => 40],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Limonada casera',            'price' => 350,  'stock' => 70],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Té helado de melocotón',     'price' => 320,  'stock' => 60],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Granizado de limón',         'price' => 350,  'stock' => 50],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Horchata',                   'price' => 380,  'stock' => 50],
            ['fam' => 'Bebidas Frías', 'tax' => $ivaGeneral, 'name' => 'Kombucha',                   'price' => 450,  'stock' => 40],

            // ── Cervezas ──
            // De barril: un único producto con variantes (caña / doble / jarra).
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Cerveza de barril',              'price' => 200,  'stock' => 999],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Clara con limón',                'price' => 220,  'stock' => 999],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Estrella Galicia 33cl',          'price' => 280,  'stock' => 300],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Mahou Cinco Estrellas 33cl',     'price' => 280,  'stock' => 300],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Estrella Damm 33cl',             'price' => 280,  'stock' => 300],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Voll Damm 33cl',                 'price' => 320,  'stock' => 200],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Inedit Damm 33cl',               'price' => 380,  'stock' => 150],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Alhambra Reserva 1925',          'price' => 350,  'stock' => 200],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'San Miguel 0,0',                 'price' => 280,  'stock' => 150],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Heineken 0.0',                   'price' => 290,  'stock' => 150],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Cerveza sin alcohol',            'price' => 280,  'stock' => 100],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Heineken 33cl',                  'price' => 320,  'stock' => 200],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Corona 33cl',                    'price' => 380,  'stock' => 150],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Budweiser 33cl',                 'price' => 380,  'stock' => 100],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Guinness 50cl',                  'price' => 480,  'stock' => 80],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Paulaner Weissbier 50cl',        'price' => 490,  'stock' => 80],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Erdinger Weissbier 50cl',        'price' => 490,  'stock' => 80],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Franziskaner Hefe-Weissbier 50cl', 'price' => 490, 'stock' => 60],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'IPA artesana La Pirata',         'price' => 480,  'stock' => 60],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'IPA artesana Garage Soup',       'price' => 480,  'stock' => 60],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Pale Ale artesana',              'price' => 460,  'stock' => 60],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Stout artesana',                 'price' => 480,  'stock' => 50],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Cerveza de trigo artesana',      'price' => 460,  'stock' => 60],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Lambic Kriek',                   'price' => 520,  'stock' => 40],
            ['fam' => 'Cervezas', 'tax' => $ivaGeneral, 'name' => 'Sidra natural',                  'price' => 380,  'stock' => 80],

            // ── Vinos ──
            // Casa (copa + botella)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Tinto de la casa',                              'price' => 350,  'stock' => 999],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Blanco de la casa',                             'price' => 350,  'stock' => 999],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Rosado de la casa',                             'price' => 350,  'stock' => 999],
            // Vermut (copa + botellín)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Vermut rojo',                                   'price' => 350,  'stock' => 200],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Vermut blanco',                                 'price' => 350,  'stock' => 150],
            // Tintos (copa + botella)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Rioja Crianza (D.O.Ca. Rioja)',                 'price' => 420,  'stock' => 60],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Rioja Reserva (D.O.Ca. Rioja)',                 'price' => 590,  'stock' => 40],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Ribera del Duero Roble',                        'price' => 440,  'stock' => 40],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Ribera del Duero Crianza',                      'price' => 540,  'stock' => 35],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Ribera del Duero Reserva',                      'price' => 750,  'stock' => 25],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Priorat (D.O.Q. Priorat)',                      'price' => 720,  'stock' => 25],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Toro Tempranillo',                              'price' => 480,  'stock' => 30],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Mencía Bierzo',                                 'price' => 460,  'stock' => 25],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Garnacha Calatayud',                            'price' => 390,  'stock' => 30],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Somontano Tinto',                               'price' => 420,  'stock' => 25],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Malbec (Argentina)',                            'price' => 540,  'stock' => 25],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Cabernet Sauvignon (Chile)',                    'price' => 500,  'stock' => 25],
            // Tintos premium (solo botella)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Rioja Gran Reserva (D.O.Ca. Rioja)',            'price' => 3890, 'stock' => 25],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Pinot Noir (Borgoña)',                          'price' => 3490, 'stock' => 20],
            // Blancos (copa + botella)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Albariño (D.O. Rías Baixas)',                   'price' => 480,  'stock' => 50],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Verdejo (D.O. Rueda)',                          'price' => 370,  'stock' => 60],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Godello (D.O. Valdeorras)',                     'price' => 440,  'stock' => 30],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Sauvignon Blanc (Rueda)',                       'price' => 420,  'stock' => 30],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Chardonnay (Somontano)',                        'price' => 460,  'stock' => 30],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Riesling (Alemania)',                           'price' => 520,  'stock' => 25],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Txakoli (D.O. Getariako Txakolina)',            'price' => 460,  'stock' => 25],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Pazo de Señorans',                              'price' => 630,  'stock' => 20],
            // Rosados (copa + botella)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Rosado Navarra',                                'price' => 370,  'stock' => 30],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Rosado Provence',                               'price' => 540,  'stock' => 25],
            // Espumosos (copa + botella)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Cava Brut Nature',                              'price' => 420,  'stock' => 40],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Cava Reserva',                                  'price' => 520,  'stock' => 30],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Prosecco (Italia)',                             'price' => 480,  'stock' => 30],
            // Champagne premium (solo botella)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Champagne Moët & Chandon',                      'price' => 6890, 'stock' => 15],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Champagne Veuve Clicquot',                      'price' => 7990, 'stock' => 12],
            // Generosos / Dulces (solo copa)
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Pedro Ximénez',                                 'price' => 480,  'stock' => 60],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Manzanilla',                                    'price' => 380,  'stock' => 60],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Fino',                                          'price' => 380,  'stock' => 60],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Oporto',                                        'price' => 480,  'stock' => 60],
            ['fam' => 'Vinos', 'tax' => $ivaGeneral, 'name' => 'Moscatel',                                      'price' => 380,  'stock' => 60],

            // ── Cócteles y Destilados ──
            // Cócteles clásicos
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Mojito',                  'price' => 850,  'stock' => 200],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Mojito de fresa',         'price' => 890,  'stock' => 150],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Caipirinha',              'price' => 850,  'stock' => 150],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Margarita',               'price' => 890,  'stock' => 150],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Daiquiri de fresa',       'price' => 890,  'stock' => 100],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Piña Colada',             'price' => 890,  'stock' => 100],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Cosmopolitan',            'price' => 890,  'stock' => 100],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Sex on the Beach',        'price' => 890,  'stock' => 100],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Negroni',                 'price' => 890,  'stock' => 100],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Old Fashioned',           'price' => 990,  'stock' => 80],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Manhattan',               'price' => 990,  'stock' => 80],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Whisky Sour',             'price' => 890,  'stock' => 80],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Aperol Spritz',           'price' => 850,  'stock' => 150],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Bloody Mary',             'price' => 890,  'stock' => 80],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Long Island Iced Tea',    'price' => 990,  'stock' => 60],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Cóctel sin alcohol de la casa', 'price' => 650, 'stock' => 100],
            // Destilados
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Gin tonic',               'price' => 850,  'stock' => 999],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Gin tonic premium',       'price' => 1090, 'stock' => 200],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Vodka tonic',             'price' => 800,  'stock' => 200],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Cuba libre',              'price' => 800,  'stock' => 200],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Whisky con cola',         'price' => 850,  'stock' => 200],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Ron añejo',               'price' => 750,  'stock' => 150],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Whisky JB',               'price' => 700,  'stock' => 150],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Whisky Jack Daniels',     'price' => 850,  'stock' => 120],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Whisky Macallan 12 años', 'price' => 1290, 'stock' => 60],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Tequila reposado',        'price' => 750,  'stock' => 120],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Brandy Carlos I',         'price' => 690,  'stock' => 100],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Orujo de hierbas',        'price' => 480,  'stock' => 100],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Licor de manzana',        'price' => 480,  'stock' => 80],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Limoncello',              'price' => 480,  'stock' => 80],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Baileys',                 'price' => 580,  'stock' => 80],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Chupito de tequila',      'price' => 250,  'stock' => 999],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Chupito de hierbas',      'price' => 250,  'stock' => 999],
            ['fam' => 'Cócteles y Destilados', 'tax' => $ivaGeneral, 'name' => 'Chupito de Jägermeister', 'price' => 280,  'stock' => 999],

            // ── Postres ──
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Tarta de zanahoria',          'price' => 690,  'stock' => 30],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Tarta de queso al horno',     'price' => 720,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Tarta de manzana',            'price' => 650,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Tarta de Santiago',           'price' => 650,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Coulant de chocolate',        'price' => 650,  'stock' => 30],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Cheesecake',                  'price' => 690,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Tiramisú',                    'price' => 650,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Brownie con helado',          'price' => 600,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Profiteroles',                'price' => 650,  'stock' => 20],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Sorbete de limón',            'price' => 480,  'stock' => 40],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Sorbete de mandarina',        'price' => 480,  'stock' => 40],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Helado',                      'price' => 400,  'stock' => 80],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Crema catalana',              'price' => 550,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Arroz con leche',             'price' => 520,  'stock' => 25],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Flan de huevo casero',        'price' => 480,  'stock' => 30],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Natillas',                    'price' => 450,  'stock' => 30],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Fruta de temporada',          'price' => 380,  'stock' => 50],
            ['fam' => 'Postres', 'tax' => $ivaReducido, 'name' => 'Macedonia con helado',        'price' => 520,  'stock' => 30],
        ];

        $allergensByName = $this->productAllergenMap();

        foreach ($catalog as $item) {
            $allergens = $allergensByName[$item['name']] ?? [];

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
                    'allergens' => json_encode(array_values($allergens), JSON_UNESCAPED_UNICODE),
                    'updated_at' => $now,
                    'created_at' => $now,
                    'deleted_at' => null,
                ],
            );
        }
    }

    /**
     * Mapa producto => códigos de alérgenos (Reglamento UE 1169/2011).
     *
     * Códigos válidos: gluten, crustaceans, eggs, fish, peanuts, soy, dairy,
     * nuts, celery, mustard, sesame, sulphites, lupin, molluscs.
     *
     * @return array<string,string[]>
     */
    private function productAllergenMap(): array
    {
        return [
            // ── Cafés y Desayunos ──
            'Café solo' => [],
            'Café cortado' => ['dairy'],
            'Café con leche' => ['dairy'],
            'Café americano' => [],
            'Espresso doble' => [],
            'Café bombón' => ['dairy'],
            'Leche manchada' => ['dairy'],
            'Carajillo' => ['sulphites'],
            'Cappuccino' => ['dairy'],
            'Latte macchiato' => ['dairy'],
            'Flat white' => ['dairy'],
            'Mocaccino' => ['dairy', 'soy'],
            'Café helado' => ['dairy'],
            'Matcha latte' => ['dairy'],
            'Chai latte' => ['dairy'],
            'Chocolate caliente' => ['dairy'],
            'Té o infusión' => [],
            'Tostada con tomate' => ['gluten'],
            'Tostada con mantequilla y mermelada' => ['gluten', 'dairy'],
            'Croissant a la plancha' => ['gluten', 'dairy', 'eggs'],
            'Napolitana de chocolate' => ['gluten', 'dairy', 'eggs', 'soy'],
            'Magdalena casera' => ['gluten', 'dairy', 'eggs'],
            'Churros con chocolate' => ['gluten', 'dairy'],
            'Bocadillo de jamón' => ['gluten'],
            'Bocadillo de tortilla' => ['gluten', 'eggs'],

            // ── Brunch ──
            'Tostada de aguacate' => ['gluten'],
            'Tostada de salmón' => ['gluten', 'fish', 'dairy'],
            'Tostada de hummus' => ['gluten', 'sesame'],
            'Huevos benedictinos' => ['gluten', 'eggs', 'dairy'],
            'Huevos rotos con jamón' => ['eggs'],
            'Shakshuka' => ['eggs'],
            'Pancakes con frutos rojos' => ['gluten', 'eggs', 'dairy'],
            'Tortitas americanas' => ['gluten', 'eggs', 'dairy'],
            'French toast' => ['gluten', 'eggs', 'dairy'],
            'Açaí bowl' => ['nuts'],
            'Yogur con granola' => ['dairy', 'gluten', 'nuts'],
            'Tortilla francesa' => ['eggs'],
            'Tortilla de patatas' => ['eggs'],
            'Croissant jamón y queso' => ['gluten', 'dairy', 'eggs'],
            'Bagel salmón y queso crema' => ['gluten', 'fish', 'dairy', 'sesame'],
            'Sandwich club' => ['gluten', 'eggs', 'dairy'],
            'Sandwich vegetal' => ['gluten', 'eggs'],

            // ── Entrantes y Tapas ──
            'Jamón ibérico de bellota' => [],
            'Tabla de quesos' => ['dairy'],
            'Tabla de embutidos ibéricos' => [],
            'Croquetas caseras (6 ud.)' => ['gluten', 'dairy', 'eggs'],
            'Patatas bravas' => [],
            'Patatas alioli' => ['eggs'],
            'Pimientos de Padrón' => [],
            'Boquerones en vinagre' => ['fish', 'sulphites'],
            'Anchoas del Cantábrico' => ['fish'],
            'Pulpo a la gallega' => ['molluscs'],
            'Calamares a la andaluza' => ['molluscs', 'gluten'],
            'Gambas al ajillo' => ['crustaceans'],
            'Mejillones a la marinera' => ['molluscs', 'sulphites'],
            'Almejas a la marinera' => ['molluscs', 'sulphites'],
            'Tartar de atún' => ['fish', 'soy', 'sesame'],
            'Carpaccio de ternera' => ['dairy', 'mustard'],
            'Tomate, mozzarella y albahaca' => ['dairy'],
            'Hummus con pita' => ['gluten', 'sesame'],
            'Guacamole con nachos' => ['gluten'],
            'Nachos con queso' => ['gluten', 'dairy'],
            'Alitas de pollo BBQ' => ['mustard'],
            'Edamames con sal' => ['soy'],
            'Aceitunas marinadas' => [],
            'Pan con tomate' => ['gluten'],

            // ── Ensaladas ──
            'Ensalada César' => ['gluten', 'eggs', 'dairy', 'fish', 'mustard'],
            'Ensalada de la Casa' => ['fish', 'eggs'],
            'Ensalada de quinoa' => [],
            'Ensalada de burrata' => ['dairy'],
            'Ensalada caprese' => ['dairy'],
            'Ensalada niçoise' => ['fish', 'eggs'],
            'Ensalada Waldorf' => ['nuts', 'eggs', 'celery'],
            'Ensalada de pollo crispy' => ['gluten', 'eggs', 'dairy', 'mustard'],
            'Ensalada templada de pulpo' => ['molluscs'],
            'Ensalada de queso de cabra y nueces' => ['dairy', 'nuts'],

            // ── Arroces y Pastas ──
            'Paella mixta' => ['crustaceans', 'molluscs', 'fish'],
            'Paella de verduras' => [],
            'Paella de marisco' => ['crustaceans', 'molluscs', 'fish'],
            'Arroz negro' => ['molluscs', 'crustaceans'],
            'Arroz a banda' => ['fish'],
            'Arroz del senyoret' => ['crustaceans', 'molluscs', 'fish'],
            'Fideuá de marisco' => ['gluten', 'crustaceans', 'molluscs', 'fish'],
            'Risotto de boletus' => ['dairy'],
            'Risotto de gambas' => ['dairy', 'crustaceans'],
            'Carbonara' => ['gluten', 'eggs', 'dairy'],
            'Tallarines al pesto' => ['gluten', 'dairy', 'nuts'],
            'Espaguetis a la boloñesa' => ['gluten'],
            'Espaguetis aglio e olio' => ['gluten'],
            'Penne arrabbiata' => ['gluten'],
            'Ravioli de ricotta y espinacas' => ['gluten', 'dairy', 'eggs'],
            'Gnocchi a los cuatro quesos' => ['gluten', 'dairy'],
            'Lasaña de carne' => ['gluten', 'dairy', 'eggs'],
            'Mac & cheese' => ['gluten', 'dairy'],
            'Canelones de la abuela' => ['gluten', 'dairy', 'eggs'],

            // ── Carnes ──
            'Entrecot a la brasa' => [],
            'Solomillo de ternera' => [],
            'Chuletón de vaca madurada' => [],
            'Secreto ibérico' => [],
            'Presa ibérica' => [],
            'Magret de pato' => [],
            'Cordero asado' => [],
            'Hamburguesa Manolo' => ['gluten', 'dairy', 'eggs', 'sesame', 'mustard'],
            'Hamburguesa vegetariana' => ['gluten', 'soy', 'sesame'],
            'Hamburguesa de pollo crispy' => ['gluten', 'eggs', 'dairy', 'sesame'],
            'Pollo teriyaki' => ['soy', 'gluten', 'sesame'],
            'Pollo al curry' => ['dairy'],
            'Costillas a la barbacoa' => ['mustard'],
            'Tacos de carrillera' => ['gluten'],
            'Fajitas de pollo' => ['gluten', 'dairy'],
            'Solomillo Wellington' => ['gluten', 'eggs', 'dairy'],

            // ── Pescados ──
            'Salmón a la plancha' => ['fish'],
            'Merluza al horno' => ['fish'],
            'Bacalao confitado' => ['fish'],
            'Atún rojo en tataki' => ['fish', 'soy', 'sesame'],
            'Lubina a la sal' => ['fish'],
            'Dorada a la espalda' => ['fish'],
            'Rodaballo a la plancha' => ['fish'],
            'Rape en salsa verde' => ['fish'],
            'Sepia a la plancha' => ['molluscs'],
            'Gambones a la plancha' => ['crustaceans'],

            // ── Bebidas Frías ──
            'Smoothie de frutos rojos' => ['dairy'],
            // Resto de bebidas frías sin alérgenos relevantes ⇒ [].

            // ── Cervezas (gluten + sulfitos) ──
            'Cerveza de barril' => ['gluten', 'sulphites'],
            'Clara con limón' => ['gluten', 'sulphites'],
            'Estrella Galicia 33cl' => ['gluten', 'sulphites'],
            'Mahou Cinco Estrellas 33cl' => ['gluten', 'sulphites'],
            'Estrella Damm 33cl' => ['gluten', 'sulphites'],
            'Voll Damm 33cl' => ['gluten', 'sulphites'],
            'Inedit Damm 33cl' => ['gluten', 'sulphites'],
            'Alhambra Reserva 1925' => ['gluten', 'sulphites'],
            'San Miguel 0,0' => ['gluten', 'sulphites'],
            'Heineken 0.0' => ['gluten', 'sulphites'],
            'Cerveza sin alcohol' => ['gluten', 'sulphites'],
            'Heineken 33cl' => ['gluten', 'sulphites'],
            'Corona 33cl' => ['gluten', 'sulphites'],
            'Budweiser 33cl' => ['gluten', 'sulphites'],
            'Guinness 50cl' => ['gluten', 'sulphites'],
            'Paulaner Weissbier 50cl' => ['gluten', 'sulphites'],
            'Erdinger Weissbier 50cl' => ['gluten', 'sulphites'],
            'Franziskaner Hefe-Weissbier 50cl' => ['gluten', 'sulphites'],
            'IPA artesana La Pirata' => ['gluten', 'sulphites'],
            'IPA artesana Garage Soup' => ['gluten', 'sulphites'],
            'Pale Ale artesana' => ['gluten', 'sulphites'],
            'Stout artesana' => ['gluten', 'sulphites'],
            'Cerveza de trigo artesana' => ['gluten', 'sulphites'],
            'Lambic Kriek' => ['gluten', 'sulphites'],
            'Sidra natural' => ['sulphites'],

            // ── Vinos (todos con sulfitos) ──
            'Tinto de la casa' => ['sulphites'],
            'Blanco de la casa' => ['sulphites'],
            'Rosado de la casa' => ['sulphites'],
            'Vermut rojo' => ['sulphites'],
            'Vermut blanco' => ['sulphites'],
            'Rioja Crianza (D.O.Ca. Rioja)' => ['sulphites'],
            'Rioja Reserva (D.O.Ca. Rioja)' => ['sulphites'],
            'Rioja Gran Reserva (D.O.Ca. Rioja)' => ['sulphites'],
            'Ribera del Duero Roble' => ['sulphites'],
            'Ribera del Duero Crianza' => ['sulphites'],
            'Ribera del Duero Reserva' => ['sulphites'],
            'Priorat (D.O.Q. Priorat)' => ['sulphites'],
            'Toro Tempranillo' => ['sulphites'],
            'Mencía Bierzo' => ['sulphites'],
            'Garnacha Calatayud' => ['sulphites'],
            'Somontano Tinto' => ['sulphites'],
            'Malbec (Argentina)' => ['sulphites'],
            'Cabernet Sauvignon (Chile)' => ['sulphites'],
            'Pinot Noir (Borgoña)' => ['sulphites'],
            'Albariño (D.O. Rías Baixas)' => ['sulphites'],
            'Verdejo (D.O. Rueda)' => ['sulphites'],
            'Godello (D.O. Valdeorras)' => ['sulphites'],
            'Sauvignon Blanc (Rueda)' => ['sulphites'],
            'Chardonnay (Somontano)' => ['sulphites'],
            'Riesling (Alemania)' => ['sulphites'],
            'Txakoli (D.O. Getariako Txakolina)' => ['sulphites'],
            'Pazo de Señorans' => ['sulphites'],
            'Rosado Navarra' => ['sulphites'],
            'Rosado Provence' => ['sulphites'],
            'Cava Brut Nature' => ['sulphites'],
            'Cava Reserva' => ['sulphites'],
            'Prosecco (Italia)' => ['sulphites'],
            'Champagne Moët & Chandon' => ['sulphites'],
            'Champagne Veuve Clicquot' => ['sulphites'],
            'Pedro Ximénez' => ['sulphites'],
            'Manzanilla' => ['sulphites'],
            'Fino' => ['sulphites'],
            'Oporto' => ['sulphites'],
            'Moscatel' => ['sulphites'],

            // ── Cócteles y Destilados ──
            'Mojito' => ['sulphites'],
            'Mojito de fresa' => ['sulphites'],
            'Caipirinha' => ['sulphites'],
            'Margarita' => ['sulphites'],
            'Daiquiri de fresa' => ['sulphites'],
            'Piña Colada' => ['dairy', 'sulphites'],
            'Cosmopolitan' => ['sulphites'],
            'Sex on the Beach' => ['sulphites'],
            'Negroni' => ['sulphites'],
            'Old Fashioned' => ['sulphites'],
            'Manhattan' => ['sulphites'],
            'Whisky Sour' => ['eggs', 'sulphites'],
            'Aperol Spritz' => ['sulphites'],
            'Bloody Mary' => ['celery', 'sulphites'],
            'Long Island Iced Tea' => ['sulphites'],
            'Cóctel sin alcohol de la casa' => [],
            'Gin tonic' => [],
            'Gin tonic premium' => [],
            'Vodka tonic' => [],
            'Cuba libre' => [],
            'Whisky con cola' => [],
            'Ron añejo' => [],
            'Whisky JB' => [],
            'Whisky Jack Daniels' => [],
            'Whisky Macallan 12 años' => [],
            'Tequila reposado' => [],
            'Brandy Carlos I' => ['sulphites'],
            'Orujo de hierbas' => [],
            'Licor de manzana' => ['sulphites'],
            'Limoncello' => ['sulphites'],
            'Baileys' => ['dairy'],
            'Chupito de tequila' => [],
            'Chupito de hierbas' => [],
            'Chupito de Jägermeister' => [],

            // ── Postres ──
            'Tarta de zanahoria' => ['gluten', 'eggs', 'dairy', 'nuts'],
            'Tarta de queso al horno' => ['gluten', 'dairy', 'eggs'],
            'Tarta de manzana' => ['gluten', 'dairy', 'eggs'],
            'Tarta de Santiago' => ['nuts', 'eggs'],
            'Coulant de chocolate' => ['gluten', 'dairy', 'eggs'],
            'Cheesecake' => ['gluten', 'dairy', 'eggs'],
            'Tiramisú' => ['gluten', 'dairy', 'eggs'],
            'Brownie con helado' => ['gluten', 'dairy', 'eggs', 'nuts'],
            'Profiteroles' => ['gluten', 'dairy', 'eggs'],
            'Sorbete de limón' => [],
            'Sorbete de mandarina' => [],
            'Helado' => ['dairy', 'eggs'],
            'Crema catalana' => ['dairy', 'eggs'],
            'Arroz con leche' => ['dairy'],
            'Flan de huevo casero' => ['eggs', 'dairy'],
            'Natillas' => ['dairy', 'eggs', 'gluten'],
            'Fruta de temporada' => [],
            'Macedonia con helado' => ['dairy', 'eggs'],
        ];
    }

    /**
     * Siembra modificadores (extras y acompañamientos) por producto.
     *
     * Convenciones:
     *  - Cada fila en `product_modifiers` es UNA opción dentro de un grupo.
     *  - El grupo se determina por `type` (`extra` | `accompaniment`).
     *  - `is_required` y `selection_type` se mantienen consistentes entre las
     *    opciones de un mismo grupo (el front los aplica por grupo).
     *  - Restricción de dominio: los modificadores `extra` no pueden ser `is_required`.
     *  - Los precios están en céntimos.
     */
    private function seedProductModifiers(int $restaurantId, $now): void
    {
        $productIds = DB::table('products')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id', 'name');

        // Acompañamientos típicos (single + required).
        $accSingleRequired = static function (array $options): array {
            return array_map(static fn (array $opt): array => [
                'name' => $opt[0],
                'type' => 'accompaniment',
                'is_required' => true,
                'selection_type' => 'single',
                'price' => $opt[1],
            ], $options);
        };

        // Extras (multi + opcional).
        $extrasMulti = static function (array $options): array {
            return array_map(static fn (array $opt): array => [
                'name' => $opt[0],
                'type' => 'extra',
                'is_required' => false,
                'selection_type' => 'multi',
                'price' => $opt[1],
            ], $options);
        };

        $catalog = [
            // ─────────────── Cafés y Desayunos ───────────────
            'Café con leche' => array_merge(
                $accSingleRequired([
                    ['Leche entera', 0],
                    ['Leche desnatada', 0],
                    ['Leche de soja', 30],
                    ['Leche de avena', 30],
                    ['Leche sin lactosa', 30],
                ]),
                $extrasMulti([
                    ['Descafeinado', 0],
                    ['Doble de café', 50],
                ]),
            ),
            'Cappuccino' => array_merge(
                $accSingleRequired([
                    ['Leche entera', 0],
                    ['Leche desnatada', 0],
                    ['Leche de soja', 30],
                    ['Leche de avena', 30],
                ]),
                $extrasMulti([
                    ['Cacao en polvo extra', 0],
                    ['Canela', 0],
                ]),
            ),
            'Latte macchiato' => array_merge(
                $accSingleRequired([
                    ['Leche entera', 0],
                    ['Leche desnatada', 0],
                    ['Leche de soja', 30],
                    ['Leche de avena', 30],
                ]),
                $extrasMulti([
                    ['Sirope de vainilla', 40],
                    ['Sirope de caramelo', 40],
                    ['Sirope de avellana', 40],
                ]),
            ),
            'Café solo' => $extrasMulti([
                ['Descafeinado', 0],
                ['Doble', 50],
                ['Con hielo', 0],
            ]),
            'Café cortado' => $extrasMulti([
                ['Descafeinado', 0],
                ['Con leche sin lactosa', 30],
            ]),
            'Café helado' => $extrasMulti([
                ['Bola de helado de vainilla', 150],
                ['Nata montada', 50],
            ]),
            'Chocolate caliente' => $extrasMulti([
                ['Nata montada', 50],
                ['Nubes', 50],
                ['Virutas de chocolate', 50],
            ]),
            'Té o infusión' => $accSingleRequired([
                ['Manzanilla', 0],
                ['Poleo menta', 0],
                ['Té rojo', 0],
                ['Té verde', 0],
                ['Té negro', 0],
                ['Rooibos', 0],
            ]),

            // ─────────────── Brunch ───────────────
            'Tostada de aguacate' => $extrasMulti([
                ['Huevo poché', 150],
                ['Salmón ahumado', 300],
                ['Bacon crujiente', 150],
                ['Tomate cherry', 80],
            ]),
            'Tostada de salmón' => $extrasMulti([
                ['Queso crema extra', 100],
                ['Alcaparras', 50],
                ['Huevo poché', 150],
            ]),
            'Huevos benedictinos' => array_merge(
                $accSingleRequired([
                    ['Sobre muffin inglés', 0],
                    ['Sobre pan rústico', 0],
                ]),
                $extrasMulti([
                    ['Aguacate', 150],
                    ['Salmón ahumado', 300],
                    ['Bacon extra', 150],
                ]),
            ),
            'Pancakes con frutos rojos' => $extrasMulti([
                ['Sirope de arce', 50],
                ['Nata montada', 80],
                ['Crema de chocolate', 80],
                ['Bola de helado de vainilla', 150],
            ]),
            'Açaí bowl' => $extrasMulti([
                ['Granola extra', 80],
                ['Plátano', 50],
                ['Mantequilla de cacahuete', 100],
                ['Coco rallado', 50],
            ]),
            'Tortilla francesa' => array_merge(
                $accSingleRequired([
                    ['Pan tostado', 0],
                    ['Patatas fritas', 0],
                    ['Ensalada verde', 0],
                ]),
                $extrasMulti([
                    ['Jamón serrano', 200],
                    ['Queso curado', 150],
                    ['Champiñones', 100],
                ]),
            ),
            'Croissant jamón y queso' => $extrasMulti([
                ['Huevo frito', 100],
                ['Tomate', 50],
                ['Doble de queso', 150],
            ]),

            // ─────────────── Ensaladas ───────────────
            'Ensalada César' => $extrasMulti([
                ['Pollo a la plancha', 300],
                ['Bacon crujiente', 200],
                ['Anchoas', 200],
                ['Pan crujiente extra', 50],
            ]),
            'Ensalada de la Casa' => $extrasMulti([
                ['Atún', 250],
                ['Pollo a la plancha', 300],
                ['Queso feta', 150],
                ['Aguacate', 150],
            ]),
            'Ensalada de quinoa' => $extrasMulti([
                ['Aguacate', 150],
                ['Pollo a la plancha', 300],
                ['Salmón ahumado', 350],
                ['Frutos secos', 100],
            ]),
            'Ensalada de burrata' => $extrasMulti([
                ['Tomate seco', 100],
                ['Aceite de albahaca', 50],
                ['Jamón ibérico', 350],
            ]),
            'Ensalada de pollo crispy' => $extrasMulti([
                ['Salsa miel-mostaza extra', 50],
                ['Queso cheddar', 100],
                ['Bacon crujiente', 200],
            ]),

            // ─────────────── Arroces y Pastas ───────────────
            'Paella mixta' => $extrasMulti([
                ['Alioli', 50],
                ['Pan rústico', 80],
                ['Limón extra', 0],
            ]),
            'Paella de verduras' => $extrasMulti([
                ['Alioli', 50],
                ['Pan rústico', 80],
                ['Limón extra', 0],
            ]),
            'Arroz del senyoret' => $extrasMulti([
                ['Alioli', 50],
                ['Pan rústico', 80],
                ['Limón extra', 0],
            ]),
            'Risotto de boletus' => $extrasMulti([
                ['Parmesano extra', 100],
                ['Aceite de trufa', 200],
                ['Huevo poché', 150],
            ]),
            'Carbonara' => $extrasMulti([
                ['Bacon extra', 150],
                ['Parmesano extra', 100],
                ['Yema extra', 80],
            ]),
            'Tallarines al pesto' => $extrasMulti([
                ['Parmesano extra', 100],
                ['Pollo a la plancha', 300],
                ['Piñones extra', 80],
            ]),
            'Lasaña de carne' => $extrasMulti([
                ['Queso gratinado extra', 100],
                ['Bechamel extra', 80],
            ]),

            // ─────────────── Carnes ───────────────
            'Entrecot a la brasa' => array_merge(
                $accSingleRequired([
                    ['Poco hecho', 0],
                    ['Al punto', 0],
                    ['Hecho', 0],
                    ['Muy hecho', 0],
                ]),
                $accSingleRequired([
                    ['Guarnición: Patatas fritas', 0],
                    ['Guarnición: Patatas asadas', 0],
                    ['Guarnición: Verduras a la parrilla', 0],
                    ['Guarnición: Ensalada verde', 0],
                ]),
                $extrasMulti([
                    ['Salsa de pimienta', 150],
                    ['Salsa roquefort', 150],
                    ['Foie', 350],
                ]),
            ),
            'Solomillo de ternera' => array_merge(
                $accSingleRequired([
                    ['Poco hecho', 0],
                    ['Al punto', 0],
                    ['Hecho', 0],
                    ['Muy hecho', 0],
                ]),
                $accSingleRequired([
                    ['Guarnición: Patatas fritas', 0],
                    ['Guarnición: Puré de patata', 0],
                    ['Guarnición: Verduras a la parrilla', 0],
                ]),
                $extrasMulti([
                    ['Salsa de pimienta', 150],
                    ['Reducción de Pedro Ximénez', 150],
                    ['Foie', 350],
                ]),
            ),
            'Hamburguesa Manolo' => array_merge(
                $accSingleRequired([
                    ['Punto: Poco hecha', 0],
                    ['Punto: Al punto', 0],
                    ['Punto: Hecha', 0],
                    ['Punto: Muy hecha', 0],
                ]),
                $accSingleRequired([
                    ['Guarnición: Patatas fritas', 0],
                    ['Guarnición: Patatas gajo', 0],
                    ['Guarnición: Ensalada verde', 0],
                    ['Guarnición: Verduras a la parrilla', 0],
                ]),
                $extrasMulti([
                    ['Bacon', 150],
                    ['Queso cheddar', 100],
                    ['Queso azul', 120],
                    ['Cebolla caramelizada', 80],
                    ['Huevo frito', 100],
                    ['Aguacate', 150],
                    ['Doble carne', 350],
                ]),
            ),
            'Pollo teriyaki' => array_merge(
                $accSingleRequired([
                    ['Guarnición: Arroz blanco', 0],
                    ['Guarnición: Verduras salteadas', 0],
                    ['Guarnición: Patatas fritas', 0],
                ]),
                $extrasMulti([
                    ['Salsa teriyaki extra', 100],
                    ['Sésamo tostado', 50],
                    ['Cebolleta', 50],
                ]),
            ),
            'Costillas a la barbacoa' => array_merge(
                $accSingleRequired([
                    ['Guarnición: Patatas fritas', 0],
                    ['Guarnición: Coleslaw', 0],
                    ['Guarnición: Verduras a la parrilla', 0],
                ]),
                $extrasMulti([
                    ['Salsa BBQ extra', 100],
                    ['Mazorca de maíz', 200],
                ]),
            ),

            // ─────────────── Pescados ───────────────
            'Salmón a la plancha' => array_merge(
                $accSingleRequired([
                    ['Guarnición: Verduras al vapor', 0],
                    ['Guarnición: Patatas panadera', 0],
                    ['Guarnición: Arroz salvaje', 0],
                    ['Guarnición: Ensalada verde', 0],
                ]),
                $extrasMulti([
                    ['Salsa de eneldo', 100],
                    ['Limón a la parrilla', 0],
                ]),
            ),
            'Merluza al horno' => array_merge(
                $accSingleRequired([
                    ['Guarnición: Patatas panadera', 0],
                    ['Guarnición: Verduras al vapor', 0],
                    ['Guarnición: Pisto', 0],
                ]),
                $extrasMulti([
                    ['Refrito de ajo', 50],
                    ['Almejas', 350],
                ]),
            ),
            'Bacalao confitado' => array_merge(
                $accSingleRequired([
                    ['Guarnición: Pisto', 0],
                    ['Guarnición: Patatas panadera', 0],
                    ['Guarnición: Verduras al vapor', 0],
                ]),
                $extrasMulti([
                    ['Pil-pil extra', 100],
                    ['Pimientos del piquillo', 150],
                ]),
            ),
            'Atún rojo en tataki' => array_merge(
                $accSingleRequired([
                    ['Guarnición: Ensalada wakame', 0],
                    ['Guarnición: Arroz blanco', 0],
                    ['Guarnición: Verduras salteadas', 0],
                ]),
                $extrasMulti([
                    ['Salsa ponzu', 100],
                    ['Sésamo tostado', 50],
                    ['Wasabi', 50],
                ]),
            ),

            // ─────────────── Bebidas Frías ───────────────
            'Refresco (Coca-Cola)' => $extrasMulti([
                ['Sin azúcar (Zero)', 0],
                ['Con hielo', 0],
                ['Con limón', 0],
            ]),
            'Refresco (Fanta)' => $accSingleRequired([
                ['Naranja', 0],
                ['Limón', 0],
            ]),
            'Zumo natural de naranja' => $extrasMulti([
                ['Sin pulpa', 0],
                ['Con hielo', 0],
            ]),
            'Smoothie de frutos rojos' => $extrasMulti([
                ['Con leche de avena', 30],
                ['Con yogur', 50],
                ['Con miel', 30],
            ]),
            'Limonada casera' => $extrasMulti([
                ['Con menta', 0],
                ['Con jengibre', 50],
            ]),

            // ─────────────── Vinos y Cervezas ───────────────
            'Gin tonic' => array_merge(
                $accSingleRequired([
                    ['Bombay Sapphire', 0],
                    ['Beefeater', 0],
                    ['Tanqueray', 100],
                    ["Hendrick's", 200],
                ]),
                $extrasMulti([
                    ['Rodaja de limón', 0],
                    ['Rodaja de pepino', 0],
                    ['Bayas de enebro', 0],
                    ['Pomelo', 50],
                ]),
            ),
            'Cóctel de la casa' => $accSingleRequired([
                ['Mojito', 0],
                ['Caipirinha', 0],
                ['Margarita', 0],
                ['Daiquiri de fresa', 0],
            ]),

            // ─────────────── Postres ───────────────
            'Brownie con helado' => $accSingleRequired([
                ['Helado de vainilla', 0],
                ['Helado de chocolate', 0],
                ['Helado de fresa', 0],
                ['Helado de turrón', 0],
            ]),
            'Coulant de chocolate' => $extrasMulti([
                ['Bola de helado de vainilla', 150],
                ['Nata montada', 50],
                ['Frutos rojos', 100],
            ]),
            'Cheesecake' => $extrasMulti([
                ['Salsa de frutos rojos', 50],
                ['Salsa de caramelo', 50],
                ['Nata montada', 50],
            ]),
            'Tarta de zanahoria' => $extrasMulti([
                ['Nata montada', 50],
                ['Helado de vainilla', 150],
            ]),
            'Tiramisú' => $extrasMulti([
                ['Cacao extra', 0],
                ['Licor amaretto', 150],
            ]),
            'Sorbete de limón' => $extrasMulti([
                ['Cava', 200],
                ['Vodka', 250],
            ]),
            'Crema catalana' => $extrasMulti([
                ['Galleta extra', 50],
            ]),
        ];

        foreach ($catalog as $productName => $modifiers) {
            if (! isset($productIds[$productName])) {
                continue;
            }

            $productId = (int) $productIds[$productName];
            $sortOrder = 0;

            foreach ($modifiers as $modifier) {
                DB::table('product_modifiers')->updateOrInsert(
                    [
                        'product_id' => $productId,
                        'name' => $modifier['name'],
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'type' => $modifier['type'],
                        'is_required' => $modifier['is_required'],
                        'selection_type' => $modifier['selection_type'],
                        'price' => $modifier['price'],
                        'active' => true,
                        'sort_order' => $sortOrder,
                        'updated_at' => $now,
                        'created_at' => $now,
                        'deleted_at' => null,
                    ],
                );
                $sortOrder++;
            }
        }
    }

    /**
     * Siembra variantes (tamaños / formatos) por producto.
     *
     * Convenciones:
     *  - Una variante es una alternativa MUTUAMENTE EXCLUYENTE del producto base
     *    (copa vs. botella, individual vs. para 2/4, 2/3/4 bolas...).
     *  - El front, cuando un producto tiene variantes, obliga a elegir una y su
     *    `price` reemplaza al `price` base del producto.
     *  - Los productos sin variante (refrescos, cócteles, postres puntuales,
     *    champagnes premium, vinos generosos por copa) simplemente no aparecen
     *    en este catálogo.
     */
    private function seedProductVariants(int $restaurantId, $now): void
    {
        $productIds = DB::table('products')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id', 'name');

        // Copa + Botella (vinos / espumosos por referencia).
        $copaBotella = static function (int $priceCopa, int $priceBotella, int $stockCopa = 80, int $stockBotella = 30): array {
            return [
                ['name' => 'Copa',    'price' => $priceCopa,    'stock' => $stockCopa],
                ['name' => 'Botella', 'price' => $priceBotella, 'stock' => $stockBotella],
            ];
        };

        // Individual / Para 2 (×1.7) / Para 4 (×3.2) — redondeo a múltiplos de 10 cts.
        $round10 = static fn (int $value): int => (int) (round($value / 10) * 10);
        $paellaRaciones = static function (int $individual, int $stock = 30) use ($round10): array {
            return [
                ['name' => 'Individual', 'price' => $individual,                                'stock' => $stock],
                ['name' => 'Para 2',     'price' => $round10((int) round($individual * 1.7)),  'stock' => $stock],
                ['name' => 'Para 4',     'price' => $round10((int) round($individual * 3.2)),  'stock' => $stock],
            ];
        };

        $catalog = [
            // ─────────────── Cervezas de barril ───────────────
            'Cerveza de barril' => [
                ['name' => 'Caña',  'price' => 200, 'stock' => 999],
                ['name' => 'Doble', 'price' => 290, 'stock' => 999],
                ['name' => 'Jarra', 'price' => 480, 'stock' => 999],
            ],
            'Clara con limón' => [
                ['name' => 'Caña',  'price' => 220, 'stock' => 999],
                ['name' => 'Doble', 'price' => 320, 'stock' => 999],
                ['name' => 'Jarra', 'price' => 510, 'stock' => 999],
            ],

            // ─────────────── Vinos de la casa ───────────────
            'Tinto de la casa' => $copaBotella(350, 1490, 999, 50),
            'Blanco de la casa' => $copaBotella(350, 1490, 999, 50),
            'Rosado de la casa' => $copaBotella(350, 1490, 999, 50),

            // ─────────────── Vermut (copa + botellín) ───────────────
            'Vermut rojo' => [
                ['name' => 'Copa',     'price' => 350,  'stock' => 200],
                ['name' => 'Botellín', 'price' => 1090, 'stock' => 30],
            ],
            'Vermut blanco' => [
                ['name' => 'Copa',     'price' => 350,  'stock' => 150],
                ['name' => 'Botellín', 'price' => 1090, 'stock' => 25],
            ],

            // ─────────────── Tintos ───────────────
            'Rioja Crianza (D.O.Ca. Rioja)' => $copaBotella(420, 1890, 100, 60),
            'Rioja Reserva (D.O.Ca. Rioja)' => $copaBotella(590, 2690, 80, 40),
            'Ribera del Duero Roble' => $copaBotella(440, 1990, 80, 40),
            'Ribera del Duero Crianza' => $copaBotella(540, 2490, 80, 35),
            'Ribera del Duero Reserva' => $copaBotella(750, 3490, 60, 25),
            'Priorat (D.O.Q. Priorat)' => $copaBotella(720, 3290, 60, 25),
            'Toro Tempranillo' => $copaBotella(480, 2190, 80, 30),
            'Mencía Bierzo' => $copaBotella(460, 2090, 80, 25),
            'Garnacha Calatayud' => $copaBotella(390, 1790, 80, 30),
            'Somontano Tinto' => $copaBotella(420, 1890, 80, 25),
            'Malbec (Argentina)' => $copaBotella(540, 2490, 60, 25),
            'Cabernet Sauvignon (Chile)' => $copaBotella(500, 2290, 60, 25),

            // ─────────────── Blancos ───────────────
            'Albariño (D.O. Rías Baixas)' => $copaBotella(480, 2190, 100, 50),
            'Verdejo (D.O. Rueda)' => $copaBotella(370, 1690, 120, 60),
            'Godello (D.O. Valdeorras)' => $copaBotella(440, 1990, 80, 30),
            'Sauvignon Blanc (Rueda)' => $copaBotella(420, 1890, 80, 30),
            'Chardonnay (Somontano)' => $copaBotella(460, 2090, 80, 30),
            'Riesling (Alemania)' => $copaBotella(520, 2390, 60, 25),
            'Txakoli (D.O. Getariako Txakolina)' => $copaBotella(460, 2090, 60, 25),
            'Pazo de Señorans' => $copaBotella(630, 2890, 40, 20),

            // ─────────────── Rosados ───────────────
            'Rosado Navarra' => $copaBotella(370, 1690, 80, 30),
            'Rosado Provence' => $copaBotella(540, 2490, 50, 25),

            // ─────────────── Espumosos ───────────────
            'Cava Brut Nature' => $copaBotella(420, 1890, 80, 40),
            'Cava Reserva' => $copaBotella(520, 2390, 60, 30),
            'Prosecco (Italia)' => $copaBotella(480, 2190, 60, 30),

            // ─────────────── Arroces (Individual / Para 2 / Para 4) ───────────────
            'Paella mixta' => $paellaRaciones(1450, 30),
            'Paella de verduras' => $paellaRaciones(1350, 30),
            'Paella de marisco' => $paellaRaciones(1690, 25),
            'Arroz negro' => $paellaRaciones(1590, 25),
            'Arroz a banda' => $paellaRaciones(1490, 25),
            'Arroz del senyoret' => $paellaRaciones(1550, 25),
            'Fideuá de marisco' => $paellaRaciones(1590, 25),

            // ─────────────── Helado (bolas) ───────────────
            'Helado' => [
                ['name' => '2 bolas', 'price' => 400, 'stock' => 80],
                ['name' => '3 bolas', 'price' => 580, 'stock' => 80],
                ['name' => '4 bolas', 'price' => 720, 'stock' => 80],
            ],
        ];

        foreach ($catalog as $productName => $variants) {
            if (! isset($productIds[$productName])) {
                continue;
            }

            $productId = (int) $productIds[$productName];
            $sortOrder = 0;

            foreach ($variants as $variant) {
                DB::table('product_variants')->updateOrInsert(
                    [
                        'product_id' => $productId,
                        'name' => $variant['name'],
                    ],
                    [
                        'uuid' => (string) Str::uuid(),
                        'price' => $variant['price'],
                        'stock' => $variant['stock'],
                        'active' => true,
                        'sort_order' => $sortOrder,
                        'updated_at' => $now,
                        'created_at' => $now,
                        'deleted_at' => null,
                    ],
                );
                $sortOrder++;
            }
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
     * @param  array<string,int>  $zoneIds  zone name => id
     */
    private function seedTables(int $restaurantId, array $zoneIds, $now): void
    {
        $layout = [
            'Terraza' => ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8'],
            'Salón Principal' => ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10', 'S11', 'S12'],
            'Barra' => ['B1', 'B2', 'B3', 'B4', 'B5', 'B6'],
            'Reservado' => ['R1', 'R2'],
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
     * @param  array<string,int>  $userIds  email => id
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
