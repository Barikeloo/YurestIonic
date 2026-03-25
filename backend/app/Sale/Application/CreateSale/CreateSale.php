<?php

namespace App\Sale\Application\CreateSale;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
    ): CreateSaleResponse {
        $sale = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            openedByUserId: Uuid::create($openedByUserId),
        );

        $this->saleRepository->save($sale);

        return CreateSaleResponse::create($sale);
    }
}
