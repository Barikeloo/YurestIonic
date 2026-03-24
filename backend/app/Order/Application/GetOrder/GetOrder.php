<?php

namespace App\Order\Application\GetOrder;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;

final class GetOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(string $id): ?GetOrderResponse
    {
        $order = $this->orderRepository->getById($id);

        if ($order === null) {
            return null;
        }

        return GetOrderResponse::create($order);
    }
}
