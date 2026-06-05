<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the Bar Manolo restaurant with a backdated audit-log corpus
 * so the retention pipeline can be exercised end-to-end:
 *
 *  - ~40 events with created_at between 1 year and 95 days ago, so
 *    `audit:archive-old --older-than-days=90` will pick them up.
 *  - 5 events within the last week so the "live" surface stays
 *    non-empty after the archive run.
 *
 * Hashes are placeholder values (sha256 of all zeros). The chain
 * verifier will report the demo rows as broken — that is fine, the
 * seeder exists for panel/archive/export E2E coverage, not for chain
 * integrity testing (which lives in AuditVerifyChainTest +
 * AuditRetentionLifecycleTest with real hashing).
 *
 * The seeder is idempotent against the demo restaurant: it wipes the
 * previous retention-demo rows (identified by entity_type
 * 'retention_demo') before inserting again, so re-running it does not
 * stack data.
 *
 * Pre-requisite: SaonaDemoSeeder must have run so the restaurant
 * exists.
 *
 * Ejecutar con:
 *   php artisan db:seed --class=RetentionDemoSeeder
 */
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

        // Wipe previous demo rows so re-runs are idempotent.
        DB::table('audit_logs')
            ->where('restaurant_id', $restaurantId)
            ->where('entity_type', self::ENTITY_TYPE)
            ->delete();

        // Round-robin a handful of Bar Manolo employees through the seeded
        // events so the panel's "top usuarios" widget has something to show.
        // Falls back to null if no users are linked to the restaurant.
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

    /**
     * Plan of (offset_days, action, category, severity) tuples.
     * Offsets > 90 will be archived by `audit:archive-old`; offsets ≤ 90
     * stay live so the registry has something to show afterwards.
     *
     * @return list<array{0: int, 1: string, 2: string, 3: string}>
     */
    private function buildPlan(): array
    {
        $plan = [];

        // Spread the archivable corpus from 365 to 95 days, ~6 per month.
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
                if ($i > 3) break; // 4 events per offset = 40 archivable rows total.
                $plan[] = [$base - $i, $action, $category, $severity];
            }
        }

        // 5 live rows spread across the last week.
        foreach ([0, 1, 2, 4, 6] as $offset) {
            $plan[] = [$offset, 'sale.recorded', 'sale', 'info'];
        }

        return $plan;
    }
}
