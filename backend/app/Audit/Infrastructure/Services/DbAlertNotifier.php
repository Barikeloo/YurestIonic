<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Services;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AlertNotifierInterface;
use App\Audit\Infrastructure\Persistence\Models\EloquentAuditAlert;
use App\Restaurant\Infrastructure\Persistence\Models\EloquentRestaurant;
use App\User\Infrastructure\Persistence\Models\EloquentUser;

final class DbAlertNotifier implements AlertNotifierInterface
{
    public function notifyCriticalAnomaly(AuditEventDraft $draft, string $anomalyKind): void
    {
        $restaurantId = EloquentRestaurant::query()
            ->where('uuid', $draft->restaurantId->value())
            ->value('id');

        if ($restaurantId === null) {
            return;
        }

        $userId = null;
        if ($draft->userId !== null) {
            $userId = EloquentUser::query()
                ->where('uuid', $draft->userId->value())
                ->value('id');
        }

        EloquentAuditAlert::query()->withoutGlobalScopes()->create([
            'uuid' => \Ramsey\Uuid\Uuid::uuid4()->toString(),
            'restaurant_id' => $restaurantId,
            'action' => $draft->slug->value(),
            'anomaly_kind' => $anomalyKind,
            'entity_type' => $draft->entityType,
            'entity_id' => $draft->entityId,
            'summary' => $draft->metadata['summary'] ?? null,
            'metadata' => $draft->metadata,
            'user_id' => $userId,
            'device_id' => $draft->deviceId,
            'created_at' => now(),
        ]);
    }
}
