<?php

declare(strict_types=1);

namespace App\Sale\Application\GetOrderPaidTotal;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetOrderPaidTotal
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(GetOrderPaidTotalCommand $command): GetOrderPaidTotalResponse
    {
        $sales = $this->saleRepository->findAllByOrderId(Uuid::create($command->orderId));

        $total = 0;
        foreach ($sales as $sale) {
            if ($sale->isCancelled()) {
                continue;
            }
            $total += $sale->total()->value();
        }

        return GetOrderPaidTotalResponse::create(totalCents: $total);
    }
}
