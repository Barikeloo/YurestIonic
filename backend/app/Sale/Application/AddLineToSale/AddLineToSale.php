<?php

namespace App\Sale\Application\AddLineToSale;

use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class AddLineToSale
{
    public function __construct(
        private readonly SaleLineRepositoryInterface $saleLineRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $saleId,
        string $orderLineId,
        string $userId,
        int $quantity,
        int $price,
        int $taxPercentage,
    ): AddLineToSaleResponse {
        $saleLine = SaleLine::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            saleId: Uuid::create($saleId),
            orderLineId: Uuid::create($orderLineId),
            userId: Uuid::create($userId),
            quantity: $quantity,
            price: $price,
            taxPercentage: $taxPercentage,
        );

        $this->saleLineRepository->save($saleLine);

        return AddLineToSaleResponse::create($saleLine);
    }
}
