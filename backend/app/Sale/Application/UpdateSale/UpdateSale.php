<?php

namespace App\Sale\Application\UpdateSale;

use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use InvalidArgumentException;

final class UpdateSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SaleLineRepositoryInterface $saleLineRepository,
    ) {}

    public function __invoke(
        string $id,
        string $closedByUserId,
        int $ticketNumber,
    ): ?UpdateSaleResponse {
        $sale = $this->saleRepository->getById($id);

        if ($sale === null) {
            return null;
        }

        if ($sale->getClosedByUserId() !== null) {
            throw new InvalidArgumentException('Sale is already closed.');
        }

        $saleLines = $this->saleLineRepository->findBySaleId($sale->getId());

        if ($saleLines === []) {
            throw new InvalidArgumentException('A sale must have at least one line before closing.');
        }

        $total = 0;
        foreach ($saleLines as $saleLine) {
            $lineBase = $saleLine->getPrice() * $saleLine->getQuantity();
            $lineWithTax = intdiv($lineBase * (100 + $saleLine->getTaxPercentage()), 100);
            $total += $lineWithTax;
        }

        $sale->close(
            closedByUserId: Uuid::create($closedByUserId),
            ticketNumber: $ticketNumber,
            total: $total,
        );

        $this->saleRepository->save($sale);

        return UpdateSaleResponse::create($sale);
    }
}
