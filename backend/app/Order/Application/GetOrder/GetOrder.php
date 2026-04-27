<?php

namespace App\Order\Application\GetOrder;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(string $id): ?GetOrderResponse
    {
        $orderId = Uuid::create($id);
        $order = $this->orderRepository->findByUuid($orderId);

        if ($order === null) {
            return null;
        }

        return GetOrderResponse::create($order);
    }
}
