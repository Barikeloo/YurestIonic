<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RetentionDemoSeeder extends Seeder
{
    private const RESTAURANT_EMAIL = 'barmanolo@gmail.com';
    private const ENTITY_TYPE = 'retention_demo';

    public function run(): void
    {
        $restaurantId = (int) DB::table('restaurants')
            ->where('email', self::RESTAURANT_EMAIL)
            ->value('id');

        if ($restaurantId === 0) {
            $this->command?->error(
                'Bar Manolo restaurant not found. Run SaonaDemoSeeder first.',
            );
            return;
        }

        DB::table('audit_logs')
            ->where('restaurant_id', $restaurantId)
            ->where('entity_type', self::ENTITY_TYPE)
            ->delete();

        $userIds = DB::table('users')
            ->where('restaurant_id', $restaurantId)
            ->pluck('id')
            ->all();

        $placeholderHash = str_repeat('0', 64);
        $inserted = 0;
        $oldest = null;

        foreach ($this->buildPlan() as $index => [$offsetDays, $action, $category, $severity]) {
            $createdAt = (new \DateTimeImmutable("-{$offsetDays} days"))->format('Y-m-d H:i:s');
            $userId = count($userIds) > 0 ? $userIds[$index % count($userIds)] : null;

            DB::table('audit_logs')->insert([
                'uuid' => (string) Str::uuid(),
                'restaurant_id' => $restaurantId,
                'entity_type' => self::ENTITY_TYPE,
                'entity_id' => (string) Str::uuid(),
                'action' => $action,
                'category' => $category,
                'severity' => $severity,
                'summary' => "Retention demo · {$action}",
                'reason' => null,
                'session_id' => null,
                'anomaly_kind' => null,
                'integrity_hash' => $placeholderHash,
                'prev_hash' => null,
                'metadata' => json_encode(['offset_days' => $offsetDays]),
                'user_id' => $userId,
                'before' => null,
                'after' => null,
                'ip_address' => '127.0.0.1',
                'device_id' => 'retention-demo',
                'created_at' => $createdAt,
                'archived_at' => null,
            ]);

            $inserted++;
            if ($oldest === null || $createdAt < $oldest) {
                $oldest = $createdAt;
            }
        }

        $this->command?->info("Retention demo: {$inserted} audit rows seeded (oldest: {$oldest}).");
    }

    private function buildPlan(): array
    {
        $plan = [];

        $catalog = [
            ['caja.opened', 'caja', 'info'],
            ['caja.movement_in', 'caja', 'info'],
            ['caja.movement_out', 'caja', 'info'],
            ['order.created', 'order', 'info'],
            ['sale.recorded', 'sale', 'info'],
            ['caja.closed', 'caja', 'info'],
        ];
        $offsets = [355, 320, 290, 255, 220, 190, 160, 130, 100, 95];
        foreach ($offsets as $base) {
            foreach ($catalog as $i => [$action, $category, $severity]) {
                if ($i > 3) break;

                $plan[] = [$base - $i, $action, $category, $severity];
            }
        }

        foreach ([0, 1, 2, 4, 6] as $offset) {
            $plan[] = [$offset, 'sale.recorded', 'sale', 'info'];
        }

        return $plan;
    }
}
