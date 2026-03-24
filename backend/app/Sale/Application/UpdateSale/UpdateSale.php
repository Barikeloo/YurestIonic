<?php

namespace App\Sale\Application\UpdateSale;

use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

final class UpdateSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(
        string $id,
        ?int $ticketNumber = null,
        ?int $total = null,
    ): ?UpdateSaleResponse {
        $sale = $this->saleRepository->getById($id);

        if ($sale === null) {
            return null;
        }

        if ($ticketNumber !== null) {
            $sale->updateTicketNumber($ticketNumber);
        }

        if ($total !== null) {
            $sale->updateTotal($total);
        }

        $this->saleRepository->save($sale);

        return UpdateSaleResponse::create($sale);
    }
}
