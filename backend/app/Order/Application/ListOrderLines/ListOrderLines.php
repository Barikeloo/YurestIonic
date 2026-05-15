<?php

namespace App\Order\Application\ListOrderLines;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Product\Domain\Interfaces\ProductRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ListOrderLines
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {}

    public function __invoke(ListOrderLinesCommand $command): array
    {
        $orderLines = $this->orderLineRepository->findByOrderId(Uuid::create($command->orderId));

        return array_map(
            function ($orderLine): ListOrderLinesResponse {
                $product = $this->productRepository->findById($orderLine->productId()->value());

                return ListOrderLinesResponse::create(
                    orderLine: $orderLine,
                    productName: $product?->name()->value(),
                );
            },
            $orderLines,
        );
    }
}
