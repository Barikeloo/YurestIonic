<?php

declare(strict_types=1);

namespace App\Sale\Application\DeleteSale;

use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(DeleteSaleCommand $command): void
    {
        $sale = $this->saleRepository->findByUuid(Uuid::create($command->id))
            ?? throw SaleNotFoundException::withId($command->id);

        $this->saleRepository->delete($sale->id());
    }
}
