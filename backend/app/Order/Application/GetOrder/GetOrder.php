<?php

namespace App\Order\Application\GetOrder;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(GetOrderCommand $command): GetOrderResponse
    {
        $orderId = Uuid::create($command->id);
        $order = $this->orderRepository->findByUuid($orderId);

        if ($order === null) {
            throw OrderNotFoundException::withId($command->id);
        }

        return GetOrderResponse::create($order);
    }
}
