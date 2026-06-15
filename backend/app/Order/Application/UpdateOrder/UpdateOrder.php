<?php

declare(strict_types=1);

namespace App\Order\Application\UpdateOrder;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(UpdateOrderCommand $command): UpdateOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id))
            ?? throw OrderNotFoundException::withId($command->id);

        if ($command->diners !== null) {
            $order->updateDiners(OrderDiners::create($command->diners));
        }

        $this->orderRepository->save($order);

        $this->eventBus->publish(...$order->pullDomainEvents());

        return UpdateOrderResponse::create($order);
    }
}
