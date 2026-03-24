<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\CreateOrder\CreateOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PostController
{
    public function __construct(
        private readonly CreateOrder $createOrder,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
            'table_id' => ['required', 'string', 'uuid'],
            'opened_by_user_id' => ['required', 'string', 'uuid'],
            'diners' => ['required', 'integer', 'min:1'],
        ]);

        $response = ($this->createOrder)(
            restaurantId: $validated['restaurant_id'],
            tableId: $validated['table_id'],
            openedByUserId: $validated['opened_by_user_id'],
            diners: $validated['diners'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
