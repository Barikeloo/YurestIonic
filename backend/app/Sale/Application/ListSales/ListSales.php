<?php

namespace App\Sale\Application\ListSales;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

final class ListSales
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(): array
    {
        $sales = $this->saleRepository->all();

        return array_map(
            static fn ($sale): array => ListSalesResponse::create($sale)->toArray(),
            $sales,
        );
    }
}
