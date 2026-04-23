<?php

namespace App\Sale\Infrastructure\Entrypoint\Http;

use App\Sale\Application\GetOrderPaidTotal\GetOrderPaidTotal;
use Illuminate\Http\JsonResponse;

final class GetOrderPaidTotalController
{
    public function __construct(
        private readonly GetOrderPaidTotal $getOrderPaidTotal,
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $total = ($this->getOrderPaidTotal)($orderId);

        return new JsonResponse(['total_cents' => $total]);
    }
}
