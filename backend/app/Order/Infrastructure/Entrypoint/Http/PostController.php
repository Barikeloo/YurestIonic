<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\CreateOrder\CreateOrder;
use App\Shared\Infrastructure\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController
{
    public function __construct(
        private readonly CreateOrder $createOrder,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'table_id' => ['required', 'string', 'uuid'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'diners' => ['required', 'integer', 'min:1'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new \RuntimeException('Tenant context is required.');
        }

        try {
            $response = ($this->createOrder)(
                restaurantId: $restaurantId,
                tableId: $validated['table_id'],
                openedByUserId: $validated['opened_by_user_id'],
                diners: $validated['diners'],
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 409);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
