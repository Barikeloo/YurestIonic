<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateSale;

use App\Sale\Domain\Event\SaleClosed;
use App\Sale\Domain\Exception\SaleAlreadyClosedException;
use App\Sale\Domain\Exception\SaleMustHaveLinesException;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SaleLineRepositoryInterface $saleLineRepository,
        private readonly EventBusInterface $eventBus,
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

        $before = [
            'closed_by_user_id' => $sale->closedByUserId()?->value(),
            'ticket_number' => $sale->ticketNumber()?->value(),
            'total_cents' => $sale->total()?->value(),
        ];

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

        $this->eventBus->publish(new SaleClosed(
            saleId: $sale->id()->value(),
            closedByUserIdBefore: $before['closed_by_user_id'],
            ticketNumberBefore: $before['ticket_number'],
            totalCentsBefore: $before['total_cents'],
            closedByUserIdAfter: $sale->closedByUserId()?->value(),
            ticketNumberAfter: $sale->ticketNumber()?->value(),
            totalCentsAfter: $sale->total()?->value(),
            totalFormatted: number_format($total / 100, 2).' €',
            linesCount: count($saleLines),
        ));

        return UpdateSaleResponse::fromSale($sale);
    }
}
