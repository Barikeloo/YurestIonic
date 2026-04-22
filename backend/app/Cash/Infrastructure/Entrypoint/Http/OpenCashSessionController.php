<?php

declare(strict_types=1);

namespace App\Cash\Infrastructure\Entrypoint\Http;

use App\Cash\Application\OpenCashSession\OpenCashSession;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OpenCashSessionController
{
    public function __construct(
        private readonly OpenCashSession $openCashSession,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:100'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'initial_amount_cents' => ['required', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $response = ($this->openCashSession)(
            restaurantId: $this->tenantContext->restaurantUuid(),
            deviceId: $validated['device_id'],
            openedByUserId: $validated['opened_by_user_id'],
            initialAmountCents: $validated['initial_amount_cents'],
            notes: $validated['notes'] ?? null,
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
