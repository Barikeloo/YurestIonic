<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\AddLineToOrder\AddLineToOrder;
use InvalidArgumentException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AddLineController
{
    public function __construct(
        private readonly AddLineToOrder $addLineToOrder,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
            'order_id' => ['required', 'string', 'uuid'],
            'product_id' => ['required', 'string', 'uuid'],
            'user_id' => ['required', 'string', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'integer', 'min:0'],
            'tax_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'diner_number' => ['nullable', 'integer', 'min:1'],
        ]);

        try {
            $response = ($this->addLineToOrder)(
                restaurantId: $validated['restaurant_id'],
                orderId: $validated['order_id'],
                productId: $validated['product_id'],
                userId: $validated['user_id'],
                quantity: $validated['quantity'],
                price: $validated['price'],
                taxPercentage: $validated['tax_percentage'],
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
