<?php

declare(strict_types=1);

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetOrderPaidTotal\GetOrderPaidTotal;
use App\Sale\Infrastructure\Entrypoint\Http\Requests\GetOrderPaidTotalRequest;
use Illuminate\Http\JsonResponse;

final class GetOrderPaidTotalController
{
    public function __construct(
        private readonly GetOrderPaidTotal $getOrderPaidTotal,
    ) {}

    public function __invoke(GetOrderPaidTotalRequest $request): JsonResponse
    {
        try {
            $response = ($this->getOrderPaidTotal)($request->toCommand());
        } catch (\Throwable $e) {
            report($e);

            return new JsonResponse(['message' => 'Internal error.'], 500);
        }

        return new JsonResponse($response->toArray(), 200);
    }
}
