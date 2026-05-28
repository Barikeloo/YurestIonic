<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Http;

use App\Audit\Infrastructure\Persistence\Models\EloquentAuditAlert;
use Illuminate\Http\JsonResponse;

final class MarkAlertReadController
{
    public function __invoke(string $uuid): JsonResponse
    {
        $alert = EloquentAuditAlert::query()
            ->withoutGlobalScopes()
            ->where('uuid', $uuid)
            ->first();

        if ($alert !== null) {
            $alert->update(['read_at' => now()]);
        }

        return new JsonResponse(['ok' => true], 200);
    }
}
