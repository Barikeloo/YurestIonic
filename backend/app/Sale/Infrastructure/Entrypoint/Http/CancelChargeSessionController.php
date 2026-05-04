<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CancelChargeSession\CancelChargeSession;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CancelChargeSessionController
{
    public function __construct(
        private readonly CancelChargeSession $cancelChargeSession,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'cancelled_by_user_id' => ['required', 'string', 'uuid'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        try {
            $response = ($this->cancelChargeSession)(
                chargeSessionId: $id,
                cancelledByUserId: $validated['cancelled_by_user_id'],
                reason: $validated['reason'] ?? null,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
