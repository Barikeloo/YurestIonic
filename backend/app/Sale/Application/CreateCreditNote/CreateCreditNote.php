<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateCreditNote;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Exception\ParentSaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\CustomerFiscalData;
use App\Sale\Domain\ValueObject\DocumentType;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateCreditNote
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly AuditRecorderInterface $auditRecorder,
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

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: Uuid::create($command->restaurantId),
            slug: ActionSlug::create('sale.credit_note_issued'),
            entityType: 'credit_note',
            entityId: $creditNote->id()->value(),
            userId: Uuid::create($command->openedByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'amount_formatted' => number_format(abs($command->totalCents) / 100, 2).' €',
            ],
        ));

        return CreateCreditNoteResponse::create($creditNote);
    }
}
