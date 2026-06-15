<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateSale\CreateSale;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController
{
    public function __construct(
        private readonly CreateSale $createSale,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'uuid'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'closed_by_user_id' => ['required', 'string', 'uuid'],
            'device_id' => ['required', 'string'],
            'payments' => ['required', 'array'],
            'payments.*.method' => ['required', 'string'],
            'payments.*.amount_cents' => ['required', 'integer', 'min:0'],
            'payments.*.diner_number' => ['nullable', 'integer', 'min:1'],
            'order_line_ids' => ['nullable', 'array'],
            'order_line_ids.*' => ['string', 'uuid'],
            'is_partial_payment' => ['nullable', 'boolean'],
            'charge_session_id' => ['nullable', 'string', 'uuid'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        $response = ($this->createSale)(
            restaurantId: $restaurantId,
            orderId: $validated['order_id'],
            openedByUserId: $validated['opened_by_user_id'],
            closedByUserId: $validated['closed_by_user_id'],
            deviceId: $validated['device_id'],
            payments: $validated['payments'],
            orderLineIds: $validated['order_line_ids'] ?? null,
            isPartialPayment: $validated['is_partial_payment'] ?? false,
            chargeSessionId: $validated['charge_session_id'] ?? null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
