<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateCreditNote;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\Sale\Domain\ValueObject\DocumentType;

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

        // Create credit note as a negative sale
        $creditNote = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            openedByUserId: Uuid::create($openedByUserId),
            cashSessionId: null, // Credit notes can be created without active cash session
            parentSaleId: Uuid::create($parentSaleId),
            documentType: DocumentType::creditNote(),
            customerFiscalData: $customerFiscalData ?? $parentSale->customerFiscalData(),
        );

        // Set negative total for credit note
        $creditNote->close(
            closedByUserId: Uuid::create($openedByUserId),
            ticketNumber: $parentSale->ticketNumber(), // Use same ticket number as parent
            total: \App\Sale\Domain\ValueObject\SaleTotal::create(-abs($totalCents)),
        );

        $this->saleRepository->save($creditNote);

        return CreateCreditNoteResponse::create($creditNote);
    }
}
