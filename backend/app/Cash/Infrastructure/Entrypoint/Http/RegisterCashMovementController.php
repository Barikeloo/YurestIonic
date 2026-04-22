<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\RegisterCashMovement\RegisterCashMovement;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RegisterCashMovementController
{
    public function __construct(
        private readonly RegisterCashMovement $registerCashMovement,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cash_session_id' => ['required', 'string', 'uuid'],
            'type' => ['required', 'string', 'in:in,out'],
            'reason_code' => ['required', 'string', 'in:change_refill,supplier_payment,tip_declared,sangria,adjustment,other'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'user_id' => ['required', 'string', 'uuid'],
            'description' => ['nullable', 'string'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        $response = ($this->registerCashMovement)(
            restaurantId: $restaurantId,
            cashSessionId: $validated['cash_session_id'],
            type: $validated['type'],
            reasonCode: $validated['reason_code'],
            amountCents: $validated['amount_cents'],
            userId: $validated['user_id'],
            description: $validated['description'] ?? null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
