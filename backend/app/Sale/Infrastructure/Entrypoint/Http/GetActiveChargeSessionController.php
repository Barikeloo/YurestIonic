<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateChargeSession\CreateChargeSessionResponse;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetActiveChargeSessionController
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'uuid'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        $orderId = Uuid::create($validated['order_id']);

        // Buscar sesión activa
        $session = $this->chargeSessionRepository->findActiveByOrderId($orderId);

        if ($session === null) {
            return new JsonResponse(
                ['message' => 'No active charge session found for this order'],
                404
            );
        }

        $response = CreateChargeSessionResponse::fromEntity($session);

        return new JsonResponse($response->toArray(), 200);
    }
}
