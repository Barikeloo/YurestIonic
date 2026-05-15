<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderTotal\GetOrderTotal;
use App\Order\Infrastructure\Entrypoint\Http\Requests\GetOrderTotalRequest;
use Illuminate\Http\JsonResponse;

final class GetOrderTotalController
{
    public function __construct(
        private readonly GetOrderTotal $getOrderTotal,
    ) {}

    public function __invoke(GetOrderTotalRequest $request): JsonResponse
    {
        try {
            $total = ($this->getOrderTotal)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse(['total_cents' => $total], 200);
    }
}
