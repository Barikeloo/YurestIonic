<?php

namespace App\Sale\Application\GetSale;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

final class GetSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(string $id): ?GetSaleResponse
    {
        $sale = $this->saleRepository->getById($id);

        if ($sale === null) {
            return null;
        }

        return GetSaleResponse::create($sale);
    }
}
