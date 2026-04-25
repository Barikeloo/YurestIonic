<?php

namespace App\Order\Application\CreateOrder;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
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
        $tableUuid = Uuid::create($tableId);
        if ($this->orderRepository->findByTableId($tableUuid) !== null) {
            throw new \DomainException('La mesa ya tiene una comanda abierta.');
        }

        $order = Order::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            tableId: $tableUuid,
            openedByUserId: Uuid::create($openedByUserId),
            diners: OrderDiners::create($diners),
        );

        $this->orderRepository->save($order);

        return CreateOrderResponse::create($order);
    }
}
