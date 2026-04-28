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
            'diner_number' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', 'string', 'in:cash,card,bizum,other'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        $response = ($this->recordPayment)(
            chargeSessionId: $id,
            dinerNumber: $validated['diner_number'],
            paymentMethod: $validated['payment_method'],
        );

        // Si la sesión se completó, devolver 200, si no 201
        $statusCode = $response->isSessionComplete ? 200 : 201;

        return new JsonResponse($response->toArray(), $statusCode);
    }
}
