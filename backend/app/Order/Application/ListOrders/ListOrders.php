<?php

namespace App\Order\Application\ListOrders;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;

final class ListOrders
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(): array
    {
        $orders = $this->orderRepository->all();

        return array_map(
            static fn ($order): array => ListOrdersResponse::create($order)->toArray(),
            $orders,
        );
    }
}
