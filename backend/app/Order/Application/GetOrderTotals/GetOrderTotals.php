<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderTotals;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Calculates order totals with tax breakdown.
 * Returns subtotal (base), tax amount, and total (gross).
 * Prices are stored as gross (VAT included), so we calculate backwards:
 * - total = Σ (price * qty)
 * - subtotal = Σ (price * qty / (1 + tax/100))
 * - tax = total - subtotal
 */
final class GetOrderTotals
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
    ) {}

    public function __invoke(string $orderId): GetOrderTotalsResponse
    {
        $orderUuid = Uuid::create($orderId);
        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);

        $total = 0;
        $subtotal = 0;

        foreach ($orderLines as $line) {
            $lineTotal = $line->price()->value() * $line->quantity()->value();
            $lineTaxRate = $line->taxPercentage()->value();

            // Calculate base from gross: base = gross / (1 + tax_rate/100)
            $lineSubtotal = (int) round($lineTotal / (1 + $lineTaxRate / 100));

            $total += $lineTotal;
            $subtotal += $lineSubtotal;
        }

        $tax = $total - $subtotal;

        return GetOrderTotalsResponse::create(
            subtotal: $subtotal,
            tax: $tax,
            total: $total,
        );
    }
}
