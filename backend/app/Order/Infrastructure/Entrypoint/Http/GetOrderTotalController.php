<?php

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderTotal\GetOrderTotal;
use Illuminate\Http\JsonResponse;

final class GetOrderTotalController
{
    public function __construct(
        private readonly GetOrderTotal $getOrderTotal,
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $total = ($this->getOrderTotal)($orderId);

        return new JsonResponse(['total_cents' => $total]);
    }
}
