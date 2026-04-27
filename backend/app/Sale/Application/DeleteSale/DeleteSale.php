<?php

namespace App\Sale\Application\DeleteSale;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        $saleId = Uuid::create($id);
        $sale = $this->saleRepository->findByUuid($saleId);

        if ($sale === null) {
            return false;
        }

        $this->saleRepository->delete($sale->id());

        return true;
    }
}
