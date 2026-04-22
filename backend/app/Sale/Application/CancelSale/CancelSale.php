<?php

namespace App\Sale\Application\CancelSale;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
    ) {}

    public function __invoke(
        string $saleId,
        string $cancelledByUserId,
        string $reason,
    ): CancelSaleResponse {
        $saleUuid = Uuid::create($saleId);
        $sale = $this->saleRepository->findByUuid($saleUuid);

        if ($sale === null) {
            throw new \DomainException('Sale not found.');
        }

        if ($sale->isCancelled()) {
            throw new \DomainException('Sale is already cancelled.');
        }

        $sale->cancel(
            cancelledByUserId: Uuid::create($cancelledByUserId),
            reason: $reason,
        );

        $this->saleRepository->save($sale);

        return CancelSaleResponse::create($sale);
    }
}
