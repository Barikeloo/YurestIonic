<?php

namespace App\Sale\Application\DeleteSale;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

final class DeleteSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        $sale = $this->saleRepository->getById($id);

        if ($sale === null) {
            return false;
        }

        $this->saleRepository->delete($sale->id());

        return true;
    }
}
