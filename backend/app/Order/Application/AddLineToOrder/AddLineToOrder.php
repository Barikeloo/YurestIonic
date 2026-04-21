<?php

namespace App\Order\Application\AddLineToOrder;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderLinePrice;
use App\Order\Domain\ValueObject\OrderLineQuantity;
use App\Order\Domain\ValueObject\OrderLineTaxPercentage;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use InvalidArgumentException;

final class AddLineToOrder
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly OrderRepositoryInterface $orderRepository,
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
        $order = $this->orderRepository->getById($orderId);

        if ($order === null) {
            throw new InvalidArgumentException('Order not found.');
        }

        if (! $order->status()->isOpen()) {
            throw new InvalidArgumentException('Cannot add lines to an order that is not open.');
        }

        $product = $this->productRepository->findById($productId);

        if ($product === null) {
            throw new InvalidArgumentException('Product not found.');
        }

        if (! $product->isActive()) {
            throw new InvalidArgumentException('Only active products can be sold.');
        }

        $existing = $this->orderLineRepository->findMatchingMergeableLine(
            orderId: Uuid::create($orderId),
            productId: Uuid::create($productId),
            price: $price,
            taxPercentage: $taxPercentage,
        );

        if ($existing !== null) {
            $merged = $existing->withAddedQuantity($quantity);
            $this->orderLineRepository->save($merged);

            return AddLineToOrderResponse::create($merged);
        }

        $orderLine = OrderLine::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            productId: Uuid::create($productId),
            userId: Uuid::create($userId),
            quantity: OrderLineQuantity::create($quantity),
            price: OrderLinePrice::create($price),
            taxPercentage: OrderLineTaxPercentage::create($taxPercentage),
        );

        $this->orderLineRepository->save($orderLine);

        return AddLineToOrderResponse::create($orderLine);
    }
}
