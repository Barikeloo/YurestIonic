<?php

namespace App\Sale\Application\AddLineToSale;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\ValueObject\SaleLinePrice;
use App\Sale\Domain\ValueObject\SaleLineQuantity;
use App\Sale\Domain\ValueObject\SaleLineTaxPercentage;
use App\Shared\Domain\ValueObject\Uuid;
use InvalidArgumentException;

final class AddLineToSale
{
    public function __construct(
        private readonly SaleLineRepositoryInterface $saleLineRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ProductRepositoryInterface $productRepository,
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
        $orderLine = $this->orderLineRepository->findByUuid(Uuid::create($orderLineId));

        if ($orderLine === null) {
            throw new InvalidArgumentException('Order line not found.');
        }

        $productId = $orderLine->getProductId()->value();
        $product = $this->productRepository->findById($productId);

        if ($product === null) {
            throw new InvalidArgumentException('Product not found.');
        }

        if (! $product->isActive()) {
            throw new InvalidArgumentException('Only active products can be sold.');
        }

        $saleLine = SaleLine::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            saleId: Uuid::create($saleId),
            orderLineId: Uuid::create($orderLineId),
            productId: Uuid::create($productId),
            userId: Uuid::create($userId),
            quantity: SaleLineQuantity::create($quantity),
            price: SaleLinePrice::create($price),
            taxPercentage: SaleLineTaxPercentage::create($taxPercentage),
        );

        $this->saleLineRepository->save($saleLine);

        return AddLineToSaleResponse::create($saleLine);
    }
}
