<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateSale;

use App\Sale\Domain\Exception\SaleAlreadyClosedException;
use App\Sale\Domain\Exception\SaleMustHaveLinesException;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SaleLineRepositoryInterface $saleLineRepository,
    ) {}

    public function __invoke(UpdateSaleCommand $command): UpdateSaleResponse
    {
        $sale = $this->saleRepository->findByUuid(Uuid::create($command->id))
            ?? throw SaleNotFoundException::withId($command->id);

        if ($sale->closedByUserId() !== null) {
            throw SaleAlreadyClosedException::create();
        }

        $saleLines = $this->saleLineRepository->findBySaleId($sale->id());

        if ($saleLines === []) {
            throw SaleMustHaveLinesException::create();
        }

        $total = 0;
        foreach ($saleLines as $saleLine) {
            $total += $saleLine->price()->value() * $saleLine->quantity()->value();
        }

        $sale->close(
            closedByUserId: Uuid::create($command->closedByUserId),
            ticketNumber: SaleTicketNumber::create($command->ticketNumber),
            total: SaleTotal::create($total),
        );

        $this->saleRepository->save($sale);

        return UpdateSaleResponse::fromSale($sale);
    }
}
