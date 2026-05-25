<?php

declare(strict_types=1);

namespace App\Order\Application\CancelOrder;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(CancelOrderCommand $command): CancelOrderResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id))
            ?? throw OrderNotFoundException::withId($command->id);

        $order->cancel(Uuid::create($command->cancelledByUserId));

        $this->orderRepository->save($order);

        return CancelOrderResponse::fromOrder($order);
    }
}
