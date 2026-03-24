<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\AddLineToSale\AddLineToSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AddLineController
{
    public function __construct(
        private readonly AddLineToSale $addLineToSale,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'restaurant_id' => ['required', 'string', 'uuid'],
            'sale_id' => ['required', 'string', 'uuid'],
            'order_line_id' => ['required', 'string', 'uuid'],
            'user_id' => ['required', 'string', 'uuid'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'integer', 'min:0'],
            'tax_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $response = ($this->addLineToSale)(
            restaurantId: $validated['restaurant_id'],
            saleId: $validated['sale_id'],
            orderLineId: $validated['order_line_id'],
            userId: $validated['user_id'],
            quantity: $validated['quantity'],
            price: $validated['price'],
            taxPercentage: $validated['tax_percentage'],
        );

        return new JsonResponse($response->toArray(), 201);
    }
}
