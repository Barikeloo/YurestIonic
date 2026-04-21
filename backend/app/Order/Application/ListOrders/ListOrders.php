<?php

namespace App\Order\Application\ListOrders;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;

final class ListOrders
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
    ) {}

    public function __invoke(): array
    {
        $orders = $this->orderRepository->all();

        return array_map(
            function ($order): array {
                $orderLines = $this->orderLineRepository->findByOrderId($order->id());
                $total = array_reduce(
                    $orderLines,
                    static fn (int $acc, $line): int => $acc + ($line->quantity()->value() * $line->price()->value()),
                    0,
                );

                return ListOrdersResponse::create($order, $total)->toArray();
            },
            $orders,
        );
    }
}
