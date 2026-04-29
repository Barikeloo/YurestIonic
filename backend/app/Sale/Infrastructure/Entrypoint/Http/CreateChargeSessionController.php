<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateChargeSession\CreateChargeSession;
use App\Sale\Domain\Exception\OrderHasPartialPaymentsException;
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

        try {
            $response = ($this->createChargeSession)(
                restaurantId: $restaurantId,
                orderId: $validated['order_id'],
                openedByUserId: $validated['opened_by_user_id'],
                dinersCount: $validated['diners_count'] ?? null,
            );
        } catch (OrderHasPartialPaymentsException $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
                'error_code' => 'order_has_partial_payments',
                'paid_amount_cents' => $e->paidCents,
            ], 422);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
