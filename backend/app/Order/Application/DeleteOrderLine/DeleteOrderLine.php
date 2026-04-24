<?php

namespace App\Order\Application\DeleteOrderLine;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class DeleteOrderLine
{
    public function __construct(
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(string $lineId): bool
    {
        $line = $this->orderLineRepository->findByUuid(Uuid::create($lineId));

        if ($line === null) {
            return false;
        }

        $order = $this->orderRepository->getById($line->orderId()->value());

        if ($order === null) {
            return false;
        }

        if (! $order->status()->isOpen()) {
            throw new \DomainException('Solo se pueden eliminar líneas de órdenes abiertas');
        }

        $this->orderLineRepository->delete($line->id());

        return true;
    }
}
