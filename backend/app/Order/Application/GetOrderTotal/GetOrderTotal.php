<?php

namespace App\Order\Application\GetOrderTotal;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetOrderTotal
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
    ) {}

    public function __invoke(string $orderId): int
    {
        $orderUuid = Uuid::create($orderId);
        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);

        if (empty($orderLines)) {
            return 0;
        }

        $total = 0;
        foreach ($orderLines as $line) {
            $lineBase = $line->price()->value() * $line->quantity()->value();
            $lineWithTax = (int) round($lineBase * (100 + $line->taxPercentage()->value()) / 100);
            $total += $lineWithTax;
        }

        return $total;
    }
}
