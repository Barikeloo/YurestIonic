<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateCreditNote;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\CustomerFiscalData;
use App\Sale\Domain\ValueObject\DocumentType;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateCreditNote
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $parentSaleId,
        string $openedByUserId,
        int $totalCents,
        ?array $customerFiscalData = null,
    ): CreateCreditNoteResponse {
        $parentSale = $this->saleRepository->findByUuid(Uuid::create($parentSaleId));
        if ($parentSale === null) {
            throw new \DomainException('Parent sale not found.');
        }

        $fiscalData = $customerFiscalData !== null
            ? CustomerFiscalData::fromArray($customerFiscalData)
            : $parentSale->customerFiscalData();

        // Create credit note as a negative sale
        $creditNote = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            openedByUserId: Uuid::create($openedByUserId),
            cashSessionId: null, // Credit notes can be created without active cash session
            parentSaleId: Uuid::create($parentSaleId),
            documentType: DocumentType::creditNote(),
            customerFiscalData: $fiscalData,
        );

        // Set total for credit note (positive value; sign is implicit via DocumentType::creditNote)
        $creditNote->close(
            closedByUserId: Uuid::create($openedByUserId),
            ticketNumber: $parentSale->ticketNumber(), // Use same ticket number as parent
            total: SaleTotal::create(abs($totalCents)),
        );

        $this->saleRepository->save($creditNote);

        return CreateCreditNoteResponse::create($creditNote);
    }
}
