<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderTotals\GetOrderTotals;
use Illuminate\Http\JsonResponse;

final class GetOrderTotalsController
{
    public function __construct(
        private readonly GetOrderTotals $getOrderTotals,
    ) {}

    public function __invoke(string $orderId): JsonResponse
    {
        $response = ($this->getOrderTotals)($orderId);

        return new JsonResponse($response->toArray());
    }
}
