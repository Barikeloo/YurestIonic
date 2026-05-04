<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\UpdateChargeSessionDiners\UpdateChargeSessionDiners;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UpdateChargeSessionDinersController
{
    public function __construct(
        private readonly UpdateChargeSessionDiners $updateDiners,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'diners_count' => ['required', 'integer', 'min:1'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        try {
            $response = ($this->updateDiners)(
                chargeSessionId: $id,
                newDinersCount: $validated['diners_count'],
            );

            return new JsonResponse($response->toArray(), 200);
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 422);
        }
    }
}
