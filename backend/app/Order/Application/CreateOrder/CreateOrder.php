<?php

namespace App\Order\Application\CreateOrder;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $tableId,
        string $openedByUserId,
        int $diners,
    ): CreateOrderResponse {
        $order = Order::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            tableId: Uuid::create($tableId),
            openedByUserId: Uuid::create($openedByUserId),
            diners: $diners,
        );

        $this->orderRepository->save($order);

        return CreateOrderResponse::create($order);
    }
}
