<?php

namespace App\Order\Application\UpdateOrder;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\ValueObject\OrderDiners;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateOrder
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
    ) {}

    public function __invoke(
        string $id,
        ?int $diners = null,
        ?string $action = null,
        ?string $closedByUserId = null,
    ): ?UpdateOrderResponse {
        $order = $this->orderRepository->findByUuid(Uuid::create($id));

        if ($order === null) {
            return null;
        }

        if ($diners !== null) {
            $order->updateDiners(OrderDiners::create($diners));
        }

        if ($action === 'mark-to-charge' && $closedByUserId !== null) {
            $order->markToCharge(Uuid::create($closedByUserId));
        } elseif ($action === 'cancel' && $closedByUserId !== null) {
            $order->cancel(Uuid::create($closedByUserId));
        }

        $this->orderRepository->save($order);

        return UpdateOrderResponse::create($order);
    }
}
