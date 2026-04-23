<?php

namespace App\Sale\Application\GetOrderPaidTotal;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetOrderPaidTotal
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(string $orderId): int
    {
        $orderUuid = Uuid::create($orderId);
        $sales = $this->saleRepository->findAllByOrderId($orderUuid);

        if (empty($sales)) {
            return 0;
        }

        $total = 0;
        foreach ($sales as $sale) {
            $total += $sale->total()->value();
        }

        return $total;
    }
}
