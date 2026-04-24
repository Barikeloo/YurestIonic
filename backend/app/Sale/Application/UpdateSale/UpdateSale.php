<?php

namespace App\Sale\Application\UpdateSale;

use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
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

        if ($sale->closedByUserId() !== null) {
            throw new InvalidArgumentException('Sale is already closed.');
        }

        $saleLines = $this->saleLineRepository->findBySaleId($sale->id());

        if ($saleLines === []) {
            throw new InvalidArgumentException('A sale must have at least one line before closing.');
        }

        $total = 0;
        foreach ($saleLines as $saleLine) {
            $lineBase = $saleLine->price()->value() * $saleLine->quantity()->value();
            $lineWithTax = (int) round($lineBase * (100 + $saleLine->taxPercentage()->value()) / 100);
            $total += $lineWithTax;
        }

        $sale->close(
            closedByUserId: Uuid::create($closedByUserId),
            ticketNumber: SaleTicketNumber::create($ticketNumber),
            total: SaleTotal::create($total),
        );

        $this->saleRepository->save($sale);

        return UpdateSaleResponse::create($sale);
    }
}
