<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Infrastructure\Persistence\Models\EloquentAuditAlert;
use App\Audit\Infrastructure\Persistence\Models\EloquentAuditLog;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final class ListAuditAlertsController
{
    public function __invoke(): JsonResponse
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);
        $restaurantUuid = $tenantContext->restaurantUuid();

        if ($restaurantUuid === null) {
            return new JsonResponse(['data' => [], 'unread_count' => 0], 200);
        }

        $restaurantId = EloquentRestaurant::query()
            ->where('uuid', $restaurantUuid)
            ->value('id');

        if ($restaurantId === null) {
            return new JsonResponse(['data' => [], 'unread_count' => 0], 200);
        }

        $alerts = EloquentAuditAlert::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $unreadCount = EloquentAuditAlert::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->whereNull('read_at')
            ->count();

        $resolvedLogUuids = $this->resolveLegacyAuditLogUuids($alerts, $restaurantId);

        return new JsonResponse([
            'data' => $alerts->map(static fn ($alert): array => [
                'uuid' => $alert->uuid,
                'audit_log_uuid' => $alert->audit_log_uuid ?? ($resolvedLogUuids[$alert->uuid] ?? null),
                'action' => $alert->action,
                'anomaly_kind' => $alert->anomaly_kind,
                'entity_type' => $alert->entity_type,
                'entity_id' => $alert->entity_id,
                'summary' => $alert->summary,
                'metadata' => $alert->metadata,
                'device_id' => $alert->device_id,
                'read_at' => $alert->read_at?->toIso8601String(),
                'created_at' => $alert->created_at->toIso8601String(),
            ]),
            'unread_count' => $unreadCount,
        ], 200);
    }

    /**
     * Best-effort lookup for legacy alerts that were created before audit_log_uuid was wired.
     * Matches by (restaurant_id, action, entity_type, entity_id) within a ±60s window
     * around the alert's created_at, picking the closest audit log.
     *
     * @return array<string, string> Map of alert.uuid → audit_log.uuid
     */
    private function resolveLegacyAuditLogUuids($alerts, int $restaurantId): array
    {
        $orphans = $alerts->filter(fn ($a) => $a->audit_log_uuid === null);
        if ($orphans->isEmpty()) {
            return [];
        }

        $resolved = [];
        foreach ($orphans as $alert) {
            $createdAt = $alert->created_at;
            $match = EloquentAuditLog::query()
                ->withoutGlobalScopes()
                ->where('restaurant_id', $restaurantId)
                ->where('action', $alert->action)
                ->where('entity_type', $alert->entity_type)
                ->where('entity_id', $alert->entity_id)
                ->whereBetween('created_at', [
                    $createdAt->copy()->subSeconds(60),
                    $createdAt->copy()->addSeconds(60),
                ])
                ->orderBy('created_at', 'asc')
                ->value('uuid');

            if ($match !== null) {
                $resolved[$alert->uuid] = $match;
            }
        }

        return $resolved;
    }
}
