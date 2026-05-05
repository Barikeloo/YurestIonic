<?php

declare(strict_types=1);

namespace App\Order\Application\GetOrderTotals;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

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
