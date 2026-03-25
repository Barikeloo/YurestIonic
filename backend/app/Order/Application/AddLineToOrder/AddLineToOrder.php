<?php

namespace App\Order\Application\AddLineToOrder;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use InvalidArgumentException;

final class AddLineToOrder
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $productId,
        string $userId,
        int $quantity,
        int $price,
        int $taxPercentage,
    ): AddLineToOrderResponse {
        $product = $this->productRepository->findById($productId);

        if ($product === null) {
            throw new InvalidArgumentException('Product not found.');
        }

        if (! $product->isActive()) {
            throw new InvalidArgumentException('Only active products can be sold.');
        }

        $orderLine = OrderLine::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            productId: Uuid::create($productId),
            userId: Uuid::create($userId),
            quantity: $quantity,
            price: $price,
            taxPercentage: $taxPercentage,
        );

        $this->orderLineRepository->save($orderLine);

        return AddLineToOrderResponse::create($orderLine);
    }
}
