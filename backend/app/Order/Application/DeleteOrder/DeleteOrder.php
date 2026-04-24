<?php

namespace App\Order\Application\DeleteOrder;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;

final class DeleteOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        $order = $this->orderRepository->getById($id);

        if ($order === null) {
            return false;
        }

        if (! $order->status()->isOpen()) {
            throw new \DomainException('Solo se pueden eliminar órdenes abiertas');
        }

        $this->orderRepository->delete($order->id());

        return true;
    }
}
