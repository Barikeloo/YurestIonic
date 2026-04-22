<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\CancelSale\CancelSale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CancelSaleController
{
    public function __construct(
        private readonly CancelSale $cancelSale,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'sale_id' => ['required', 'string', 'uuid'],
            'cancelled_by_user_id' => ['required', 'string', 'uuid'],
            'reason' => ['required', 'string'],
        ]);

        $response = ($this->cancelSale)(
            saleId: $validated['sale_id'],
            cancelledByUserId: $validated['cancelled_by_user_id'],
            reason: $validated['reason'],
        );

        return new JsonResponse($response->toArray(), 200);
    }
}
