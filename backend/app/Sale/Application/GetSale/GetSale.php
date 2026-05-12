<?php

namespace App\Sale\Application\GetSale;

use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(GetSaleCommand $command): GetSaleResponse
    {
        $sale = $this->saleRepository->findByUuid(Uuid::create($command->id))
            ?? throw SaleNotFoundException::withId($command->id);

        return GetSaleResponse::fromSale($sale);
    }
}
