<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\RecordChargeSessionPayment\RecordChargeSessionPayment;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RecordChargeSessionPaymentController
{
    public function __construct(
        private readonly RecordChargeSessionPayment $recordPayment,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'diner_number' => ['nullable', 'integer', 'min:1'],
            'amount_cents' => ['nullable', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,card,bizum,voucher,invitation,other'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'closed_by_user_id' => ['required', 'string', 'uuid'],
            'device_id' => ['required', 'string'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        try {
            $response = ($this->recordPayment)(
                chargeSessionId: $id,
                paymentMethod: $validated['payment_method'],
                openedByUserId: $validated['opened_by_user_id'],
                closedByUserId: $validated['closed_by_user_id'],
                deviceId: $validated['device_id'],
                dinerNumber: $validated['diner_number'] ?? null,
                amountCents: $validated['amount_cents'] ?? null,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }

        $statusCode = $response->isSessionComplete ? 200 : 201;

        return new JsonResponse($response->toArray(), $statusCode);
    }
}
