<?php

namespace App\Order\Application\UpdateOrder;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(UpdateOrderCommand $command): UpdateOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id));

        if ($order === null) {
            throw OrderNotFoundException::withId($command->id);
        }

        if ($command->diners !== null) {
            $order->updateDiners(OrderDiners::create($command->diners));
        }

        if ($command->action === 'mark-to-charge' && $command->closedByUserId !== null) {
            $order->markToCharge(Uuid::create($command->closedByUserId));
        } elseif ($command->action === 'cancel' && $command->closedByUserId !== null) {
            $order->cancel(Uuid::create($command->closedByUserId));
        }

        $this->orderRepository->save($order);

        return UpdateOrderResponse::create($order);
    }
}
