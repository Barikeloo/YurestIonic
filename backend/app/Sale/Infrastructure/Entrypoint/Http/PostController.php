<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CreateSale\CreateSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController
{
    public function __construct(
        private readonly CreateSale $createSale,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
            'order_id' => ['required', 'string', 'uuid'],
            'user_id' => ['required', 'string', 'uuid'],
            'total' => ['required', 'integer', 'min:0'],
        ]);

        $response = ($this->createSale)(
            restaurantId: $validated['restaurant_id'],
            orderId: $validated['order_id'],
            userId: $validated['user_id'],
            total: $validated['total'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
