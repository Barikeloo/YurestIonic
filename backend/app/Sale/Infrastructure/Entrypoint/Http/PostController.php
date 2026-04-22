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
            'restaurant_id'      => ['required', 'string', 'uuid'],
            'order_id'           => ['required', 'string', 'uuid'],
            'opened_by_user_id'  => ['required', 'string', 'uuid'],
            'closed_by_user_id'  => ['required', 'string', 'uuid'],
            'device_id'          => ['required', 'string'],
            'payments'           => ['required', 'array'],
            'payments.*.method'  => ['required', 'string'],
            'payments.*.amount_cents' => ['required', 'integer', 'min:0'],
        ]);

        $response = ($this->createSale)(
            restaurantId:     $validated['restaurant_id'],
            orderId:          $validated['order_id'],
            openedByUserId:   $validated['opened_by_user_id'],
            closedByUserId:   $validated['closed_by_user_id'],
            deviceId:         $validated['device_id'],
            payments:         $validated['payments'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
