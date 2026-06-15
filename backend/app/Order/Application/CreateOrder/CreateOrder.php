<?php

namespace App\Order\Application\CreateOrder;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\Exception\TableAlreadyHasOpenOrderException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(CreateOrderCommand $command): CreateOrderResponse
    {
        $tableUuid = Uuid::create($command->tableId);
        if ($this->orderRepository->findByTableId($tableUuid) !== null) {
            throw TableAlreadyHasOpenOrderException::create();
        }

        $order = Order::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            tableId: $tableUuid,
            openedByUserId: Uuid::create($command->openedByUserId),
            diners: OrderDiners::create($command->diners),
        );

        $this->orderRepository->save($order);

        $this->eventBus->publish(...$order->pullDomainEvents());

        return CreateOrderResponse::create($order);
    }
}
