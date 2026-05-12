<?php

declare(strict_types=1);

namespace App\Sale\Application\ListSales;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

final class ListSales
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(ListSalesCommand $command): array
    {
        $sales = $this->saleRepository->all();

        return array_map(
            static fn ($sale): ListSalesResponse => ListSalesResponse::fromSale($sale),
            $sales,
        );
    }
}
