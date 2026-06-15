<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateCreditNote;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Event\CreditNoteIssued;
use App\Sale\Domain\Exception\ParentSaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\CustomerFiscalData;
use App\Sale\Domain\ValueObject\DocumentType;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateCreditNote
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(CreateCreditNoteCommand $command): CreateCreditNoteResponse
    {
        $parentSale = $this->saleRepository->findByUuid(Uuid::create($command->parentSaleId))
            ?? throw ParentSaleNotFoundException::withId($command->parentSaleId);

        $fiscalData = $command->customerFiscalData !== null
            ? CustomerFiscalData::fromArray($command->customerFiscalData)
            : $parentSale->customerFiscalData();

        $creditNote = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            orderId: Uuid::create($command->orderId),
            openedByUserId: Uuid::create($command->openedByUserId),
            cashSessionId: null,
            parentSaleId: Uuid::create($command->parentSaleId),
            documentType: DocumentType::creditNote(),
            customerFiscalData: $fiscalData,
        );

        $creditNote->close(
            closedByUserId: Uuid::create($command->openedByUserId),
            ticketNumber: $parentSale->ticketNumber(),
            total: SaleTotal::create(abs($command->totalCents)),
        );

        $this->saleRepository->save($creditNote);

        $this->eventBus->publish(new CreditNoteIssued(
            creditNoteId: $creditNote->id()->value(),
            amountFormatted: number_format(abs($command->totalCents) / 100, 2).' €',
        ));

        return CreateCreditNoteResponse::create($creditNote);
    }
}
