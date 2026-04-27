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
            // Prices are stored as gross (VAT included) - consistent with CreateSale
            $total += $line->price()->value() * $line->quantity()->value();
        }

        return $total;
    }
}
