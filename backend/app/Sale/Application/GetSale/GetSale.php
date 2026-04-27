<?php

namespace App\Sale\Application\GetSale;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(string $id): ?GetSaleResponse
    {
        $saleId = Uuid::create($id);
        $sale = $this->saleRepository->findByUuid($saleId);

        if ($sale === null) {
            return null;
        }

        return GetSaleResponse::create($sale);
    }
}
