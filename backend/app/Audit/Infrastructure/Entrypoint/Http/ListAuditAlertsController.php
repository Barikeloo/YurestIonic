<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Infrastructure\Persistence\Models\EloquentAuditAlert;
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

        return new JsonResponse([
            'data' => $alerts->map(static fn ($alert): array => [
                'uuid' => $alert->uuid,
                'audit_log_uuid' => $alert->audit_log_uuid,
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
}
