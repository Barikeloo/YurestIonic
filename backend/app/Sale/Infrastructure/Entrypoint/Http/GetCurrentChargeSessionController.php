<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetCurrentChargeSessionController
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
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

        $session = $this->chargeSessionRepository->findCurrentByOrderId($orderId);

        if ($session === null) {
            return new JsonResponse(
                ['message' => 'No charge session found for this order'],
                404
            );
        }

        $response = $this->responseBuilder->build($session);

        return new JsonResponse($response->toArray(), 200);
    }
}
