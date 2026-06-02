<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateSale;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
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
        private readonly AuditRecorderInterface $auditRecorder,
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

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $sale->restaurantId(),
            slug: ActionSlug::create('sale.closed'),
            entityType: 'sale',
            entityId: $sale->id()->value(),
            userId: Uuid::create($command->closedByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: $before,
            after: [
                'closed_by_user_id' => $sale->closedByUserId()?->value(),
                'ticket_number' => $sale->ticketNumber()?->value(),
                'total_cents' => $sale->total()?->value(),
            ],
            metadata: [
                'total_formatted' => number_format($total / 100, 2).' €',
                'lines_count' => count($saleLines),
            ],
        ));

        return UpdateSaleResponse::fromSale($sale);
    }
}
