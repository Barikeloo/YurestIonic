<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateChargeSession\CreateChargeSession;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CreateChargeSessionController
{
    public function __construct(
        private readonly CreateChargeSession $createChargeSession,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'uuid'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'diners_count' => ['nullable', 'integer', 'min:1'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        $response = ($this->createChargeSession)(
            restaurantId: $restaurantId,
            orderId: $validated['order_id'],
            openedByUserId: $validated['opened_by_user_id'],
            dinersCount: $validated['diners_count'] ?? null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
