<?php

namespace App\Order\Application\UpdateOrder;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(
        string $id,
        ?int $diners = null,
        ?string $action = null, // 'close', 'cancel'
        ?string $closedByUserId = null,
    ): ?UpdateOrderResponse {
        $order = $this->orderRepository->getById($id);

        if ($order === null) {
            return null;
        }

        if ($diners !== null) {
            $order->updateDiners($diners);
        }

        if ($action === 'close' && $closedByUserId !== null) {
            $order->close(Uuid::create($closedByUserId));
        } elseif ($action === 'cancel' && $closedByUserId !== null) {
            $order->cancel(Uuid::create($closedByUserId));
        }

        $this->orderRepository->save($order);

        return UpdateOrderResponse::create($order);
    }
}
