<?php

declare(strict_types=1);

namespace App\Order\Application\ReopenOrder;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ReopenOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(ReopenOrderCommand $command): ReopenOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id))
            ?? throw OrderNotFoundException::withId($command->id);

        $order->reopen(Uuid::create($command->reopenedByUserId));

        $this->orderRepository->save($order);

        return ReopenOrderResponse::fromOrder($order);
    }
}
