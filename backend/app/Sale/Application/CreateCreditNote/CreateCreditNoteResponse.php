<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateCreditNote;

use App\Sale\Domain\Entity\Sale;

final readonly class CreateCreditNoteResponse
{
    private function __construct(
        public string $id,
        public string $orderId,
        public string $parentSaleId,
        public int $totalCents,
        public string $documentType,
    ) {
    }

    public static function create(Sale $sale): self
    {
        return new self(
            id: $sale->id()->value(),
            orderId: $sale->orderId()->value(),
            parentSaleId: $sale->parentSaleId()?->value() ?? '',
            totalCents: $sale->total()->value(),
            documentType: $sale->documentType()->value(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->orderId,
            'parent_sale_id' => $this->parentSaleId,
            'total_cents' => $this->totalCents,
            'document_type' => $this->documentType,
        ];
    }
}
