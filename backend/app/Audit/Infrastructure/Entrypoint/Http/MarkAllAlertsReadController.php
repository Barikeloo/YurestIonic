<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Infrastructure\Persistence\Models\EloquentAuditAlert;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;

final class MarkAllAlertsReadController
{
    public function __invoke(): JsonResponse
    {
        /** @var TenantContext $tenantContext */
        $tenantContext = app(TenantContext::class);
        $restaurantUuid = $tenantContext->restaurantUuid();

        if ($restaurantUuid === null) {
            return new JsonResponse(['ok' => true, 'marked' => 0], 200);
        }

        $restaurantId = EloquentRestaurant::query()
            ->where('uuid', $restaurantUuid)
            ->value('id');

        if ($restaurantId === null) {
            return new JsonResponse(['ok' => true, 'marked' => 0], 200);
        }

        $marked = EloquentAuditAlert::query()
            ->withoutGlobalScopes()
            ->where('restaurant_id', $restaurantId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return new JsonResponse(['ok' => true, 'marked' => $marked], 200);
    }
}
