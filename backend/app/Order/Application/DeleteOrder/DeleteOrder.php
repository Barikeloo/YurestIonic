<?php

namespace App\Order\Application\DeleteOrder;

use App\Order\Domain\Exception\OrderIsNotOpenException;
use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(DeleteOrderCommand $command): void
    {
        $orderId = Uuid::create($command->id);
        $order = $this->orderRepository->findByUuid($orderId);

        if ($order === null) {
            throw OrderNotFoundException::withId($command->id);
        }

        if (! $order->status()->isOpen()) {
            throw OrderIsNotOpenException::create();
        }

        $this->orderRepository->delete($order->id());
    }
}
