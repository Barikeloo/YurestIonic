<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Audit\Domain\AuditChainHasher;
use App\Audit\Domain\AuditEventCatalog;
use App\Audit\Domain\ValueObject\ActionSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditLogSeeder extends Seeder
{
    /**
     * @var list<array{slug: string, entity_type: string, entity_id_prefix: string, severity_hint?: string, anomaly?: string, reason?: string, before?: array<string, mixed>, after?: array<string, mixed>, metadata?: array<string, mixed>}>
     */
    private const TEMPLATES = [
        [
            'slug' => 'auth.login_pin_ok',
            'entity_type' => 'user_session',
            'entity_id_prefix' => 'ses-',
            'metadata' => ['method' => 'pin', 'duration_ms' => 184],
        ],
        [
            'slug' => 'auth.login_pin_failed',
            'entity_type' => 'auth_attempt',
            'entity_id_prefix' => 'att-',
            'reason' => 'PIN incorrecto',
            'metadata' => ['pin_attempt_count' => 1],
        ],
        [
            'slug' => 'order.created',
            'entity_type' => 'order',
            'entity_id_prefix' => '',
            'metadata' => ['table_id' => 'T-12'],
            'after' => ['status' => 'open', 'total_cents' => 0],
        ],
        [
            'slug' => 'order.marked_to_charge',
            'entity_type' => 'order',
            'entity_id_prefix' => '',
            'metadata' => ['total_cents' => 4780],
            'before' => ['status' => 'open'],
            'after' => ['status' => 'to-charge'],
        ],
        [
            'slug' => 'order.reopened',
            'entity_type' => 'order',
            'entity_id_prefix' => '',
            'reason' => 'Reclamación del cliente — añadir 2 cafés',
            'before' => ['status' => 'to-charge', 'closed_at' => '14:24:50'],
            'after' => ['status' => 'open', 'closed_at' => null],
            'metadata' => ['table_id' => 'T-12'],
        ],
        [
            'slug' => 'order.transferred',
            'entity_type' => 'order',
            'entity_id_prefix' => '',
            'reason' => 'Cambio de zona solicitado por el cliente',
            'before' => ['table_id' => 'T-12'],
            'after' => ['table_id' => 'T-05'],
        ],
        [
            'slug' => 'order.cancelled',
            'entity_type' => 'order',
            'entity_id_prefix' => '',
            'reason' => 'Cliente abandonó el local antes de servir',
            'before' => ['status' => 'open'],
            'after' => ['status' => 'cancelled'],
        ],
        [
            'slug' => 'caja.opened',
            'entity_type' => 'cash_session',
            'entity_id_prefix' => '',
            'metadata' => ['opening_float_cents' => 20000, 'opening_float_formatted' => '200,00 €'],
        ],
        [
            'slug' => 'caja.closed',
            'entity_type' => 'cash_session',
            'entity_id_prefix' => '',
            'metadata' => ['delta_final_cents' => 0, 'delta_final_formatted' => '0,00 €', 'z_report_id' => 'Z-2026-0143'],
            'before' => ['status' => 'open'],
            'after' => ['status' => 'closed'],
        ],
        [
            'slug' => 'caja.closed',
            'entity_type' => 'cash_session',
            'entity_id_prefix' => '',
            'reason' => 'Descuadre detectado al cuadre',
            'metadata' => ['delta_final_cents' => -240, 'delta_final_formatted' => '−2,40 €'],
            'before' => ['status' => 'open'],
            'after' => ['status' => 'closed'],
        ],
        [
            'slug' => 'caja.force_closed',
            'entity_type' => 'cash_session',
            'entity_id_prefix' => '',
            'reason' => 'Cierre forzado — cuadre no realizado en tiempo',
            'metadata' => ['delta_final_cents' => -840, 'delta_final_formatted' => '−8,40 €'],
            'before' => ['status' => 'open'],
            'after' => ['status' => 'force-closed'],
        ],
        [
            'slug' => 'caja.cash_movement',
            'entity_type' => 'cash_movement',
            'entity_id_prefix' => 'mov-',
            'reason' => 'Pago proveedor',
            'metadata' => ['movement_type' => 'out', 'amount_cents' => -5000, 'amount_formatted' => '−50,00 €'],
        ],
        [
            'slug' => 'sale.created',
            'entity_type' => 'sale',
            'entity_id_prefix' => 'V-2026-',
            'metadata' => ['total_cents' => 4780, 'total_formatted' => '47,80 €', 'payment_method' => 'card'],
        ],
        [
            'slug' => 'sale.credit_note_issued',
            'entity_type' => 'credit_note',
            'entity_id_prefix' => 'CN-',
            'reason' => 'Producto en mal estado — devolución completa de plato',
            'metadata' => ['amount_cents' => -2340, 'amount_formatted' => '−23,40 €'],
            'before' => ['sale_status' => 'completed'],
            'after' => ['sale_status' => 'partially_refunded'],
        ],
        [
            'slug' => 'product.activated',
            'entity_type' => 'product',
            'entity_id_prefix' => 'p-',
            'reason' => 'Stock repuesto: 24 unidades',
            'metadata' => ['product_name' => 'Croqueta Casera'],
            'before' => ['active' => false],
            'after' => ['active' => true, 'stock' => 24],
        ],
        [
            'slug' => 'product.deactivated',
            'entity_type' => 'product',
            'entity_id_prefix' => 'p-',
            'reason' => 'Fuera de carta temporal',
            'metadata' => ['product_name' => 'Tartar de Atún'],
            'before' => ['active' => true],
            'after' => ['active' => false],
        ],
        [
            'slug' => 'product.price_changed',
            'entity_type' => 'product',
            'entity_id_prefix' => 'p-',
            'reason' => 'Revisión trimestral de carta',
            'metadata' => [
                'product_name' => 'Hamburguesa Yurest',
                'price_before_formatted' => '9,50 €',
                'price_after_formatted' => '10,50 €',
            ],
            'before' => ['price_cents' => 950],
            'after' => ['price_cents' => 1050],
        ],
        [
            'slug' => 'table.merged',
            'entity_type' => 'merge',
            'entity_id_prefix' => 'mrg-',
            'reason' => 'Grupo grande — 8 comensales',
            'metadata' => ['tables_label' => 'Mesa 8 + Mesa 9', 'diners' => 8],
        ],
    ];

    private const DEVICES = ['TPV-01', 'TPV-02', 'TPV-03', 'TPV-Admin'];

    private const IPS = ['192.168.1.41', '192.168.1.43', '192.168.1.45', '192.168.1.10'];

    private const EVENTS_PER_RESTAURANT = 50;

    public function run(): void
    {
        $restaurants = DB::table('restaurants')->get(['id', 'uuid']);

        foreach ($restaurants as $restaurant) {
            $users = DB::table('users')
                ->where('restaurant_id', $restaurant->id)
                ->get(['id', 'uuid'])
                ->all();

            if ($users === []) {
                continue;
            }

            $this->seedForRestaurant(
                (int) $restaurant->id,
                (string) $restaurant->uuid,
                $users,
            );
        }
    }

    /**
     * Genera y persiste los eventos de auditoría para un restaurante concreto.
     * Reutilizable desde otros seeders (p. ej. SaonaDemoSeeder) tras crear sus usuarios.
     *
     * @param  list<object{id: int, uuid: string}>  $users  Usuarios del restaurante (id interno + uuid).
     */
    public function seedForRestaurant(int $restaurantId, string $restaurantUuid, array $users): void
    {
        if ($users === []) {
            return;
        }

        $hasher = app(AuditChainHasher::class);
        $now = new \DateTimeImmutable;

        $events = $this->generateEventsForRestaurant(
            $restaurantId,
            $restaurantUuid,
            $users,
            $now,
        );

        usort($events, fn (array $a, array $b): int => $a['created_at'] <=> $b['created_at']);

        $prevHash = DB::table('audit_logs')
            ->where('restaurant_id', $restaurantId)
            ->orderByDesc('id')
            ->value('integrity_hash');

        foreach ($events as $event) {
            $resolved = AuditEventCatalog::resolve(
                ActionSlug::create($event['action']),
                [
                    'entity_id' => $event['entity_id'],
                    'entity_type' => $event['entity_type'],
                    'device_id' => $event['device_id'] ?? '—',
                    'metadata' => $event['metadata'] ?? [],
                    'before' => $event['before'] ?? [],
                    'after' => $event['after'] ?? [],
                ],
            );

            $createdAtIso = $event['created_at']->format('Y-m-d H:i:s');

            $integrityHash = $hasher->compute(
                prevHash: $prevHash,
                uuid: $event['uuid'],
                restaurantUuid: $restaurantUuid,
                createdAtIso: $createdAtIso,
                actionSlug: $event['action'],
                entityType: $event['entity_type'],
                entityId: $event['entity_id'],
                userUuid: $event['user_uuid'],
                summary: $resolved['summary'],
                metadata: $event['metadata'] ?? [],
                before: $event['before'] ?? null,
                after: $event['after'] ?? null,
            );

            DB::table('audit_logs')->insert([
                'uuid' => $event['uuid'],
                'restaurant_id' => $restaurantId,
                'entity_type' => $event['entity_type'],
                'entity_id' => $event['entity_id'],
                'action' => $event['action'],
                'category' => $resolved['category']->value(),
                'severity' => $resolved['severity']->value(),
                'summary' => $resolved['summary'],
                'reason' => $event['reason'] ?? null,
                'session_id' => null,
                'anomaly_kind' => $event['anomaly_kind'] ?? null,
                'integrity_hash' => $integrityHash,
                'prev_hash' => $prevHash,
                'metadata' => json_encode($event['metadata'] ?? [], JSON_UNESCAPED_UNICODE),
                'user_id' => $event['user_id'],
                'before' => isset($event['before']) ? json_encode($event['before'], JSON_UNESCAPED_UNICODE) : null,
                'after' => isset($event['after']) ? json_encode($event['after'], JSON_UNESCAPED_UNICODE) : null,
                'ip_address' => $event['ip_address'],
                'device_id' => $event['device_id'],
                'created_at' => $createdAtIso,
            ]);

            $prevHash = $integrityHash;
        }
    }

    /**
     * @param  list<object>  $users
     * @return list<array<string, mixed>>
     */
    private function generateEventsForRestaurant(
        int $restaurantInternalId,
        string $restaurantUuid,
        array $users,
        \DateTimeImmutable $now,
    ): array {
        $events = [];
        for ($i = 0; $i < self::EVENTS_PER_RESTAURANT; $i++) {
            $template = self::TEMPLATES[array_rand(self::TEMPLATES)];
            $user = $users[array_rand($users)];

            $minutesAgo = random_int(1, 60 * 24 * 7);
            $createdAt = $now->modify("-{$minutesAgo} minutes");

            $entityId = $this->generateEntityId($template);

            $anomalyKind = null;
            $slug = $template['slug'];
            if ($slug === 'caja.closed' && ($template['metadata']['delta_final_cents'] ?? 0) !== 0) {
                $anomalyKind = 'caja_mismatch';
            }
            if ($slug === 'caja.force_closed') {
                $anomalyKind = 'caja_mismatch';
            }

            $events[] = [
                'uuid' => (string) Str::uuid(),
                'action' => $slug,
                'entity_type' => $template['entity_type'],
                'entity_id' => $entityId,
                'reason' => $template['reason'] ?? null,
                'metadata' => $template['metadata'] ?? [],
                'before' => $template['before'] ?? null,
                'after' => $template['after'] ?? null,
                'anomaly_kind' => $anomalyKind,
                'user_id' => $user->id,
                'user_uuid' => $user->uuid,
                'device_id' => self::DEVICES[array_rand(self::DEVICES)],
                'ip_address' => self::IPS[array_rand(self::IPS)],
                'created_at' => $createdAt,
            ];
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $template
     */
    private function generateEntityId(array $template): string
    {
        $prefix = $template['entity_id_prefix'] ?? '';
        if ($prefix === '') {
            return substr((string) Str::uuid(), 0, 8);
        }

        return $prefix.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
    }
}
