<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateCreditNote;

final readonly class CreateCreditNoteCommand
{
    public function __construct(
        public string $restaurantId,
        public string $orderId,
        public string $parentSaleId,
        public string $openedByUserId,
        public int $totalCents,
        public ?array $customerFiscalData,
    ) {}
}
