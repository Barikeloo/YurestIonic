<?php

namespace App\Order\Application\AddLineToOrder;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class AddLineToOrder
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
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
