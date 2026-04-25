<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\AddLineToOrder\AddLineToOrder;
use App\Order\Domain\ValueObject\OrderLineQuantity;
use App\Shared\Infrastructure\Tenant\TenantContext;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

final class AddLineController
{
    public function __construct(
        private readonly AddLineToOrder $addLineToOrder,
        private readonly TenantContext $tenantContext,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'uuid'],
            'product_id' => ['required', 'string', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1'],
            'diner_number' => ['nullable', 'integer', 'min:1'],
        ]);

        $restaurantId = $this->tenantContext->restaurantUuid();
        if ($restaurantId === null) {
            throw new RuntimeException('Tenant context is required.');
        }

        $userId = $request->session()->get('auth_user_id');
        if (! is_string($userId) || $userId === '') {
            return new JsonResponse([
                'message' => 'Authenticated user is required.',
            ], 401);
        }

        try {
            $response = ($this->addLineToOrder)(
                restaurantId: $restaurantId,
                orderId: $validated['order_id'],
                productId: $validated['product_id'],
                userId: $userId,
                quantity: OrderLineQuantity::create($validated['quantity']),
                dinerNumber: $validated['diner_number'] ?? null,
            );
        } catch (InvalidArgumentException $exception) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
            ], 422);
        }

        return new JsonResponse($response->toArray(), 201);
    }
}
