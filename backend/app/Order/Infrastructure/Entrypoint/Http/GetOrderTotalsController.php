<?php

declare(strict_types=1);

namespace App\Order\Infrastructure\Entrypoint\Http;

use App\Order\Application\GetOrderTotals\GetOrderTotals;
use Illuminate\Http\JsonResponse;

/**
 * Returns order totals with tax breakdown:
 * - subtotal_cents: base amount without VAT
 * - tax_cents: VAT amount
 * - total_cents: gross amount with VAT (same as GetOrderTotal)
 */
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
