<?php

declare(strict_types=1);

namespace App\Order\Application\MarkOrderToCharge;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class MarkOrderToCharge
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(MarkOrderToChargeCommand $command): MarkOrderToChargeResponse
    {
        $order = $this->orderRepository->findByUuid(Uuid::create($command->id))
            ?? throw OrderNotFoundException::withId($command->id);

        $order->markToCharge(Uuid::create($command->closedByUserId));

        $this->orderRepository->save($order);

        return MarkOrderToChargeResponse::fromOrder($order);
    }
}
