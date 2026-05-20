<?php

namespace App\Order\Application\GetOrderTransfers;

use App\Order\Domain\Exception\OrderNotFoundException;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Order\Domain\Interfaces\OrderTransferRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use DateTimeInterface;

class GetOrderTransfers
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private OrderTransferRepositoryInterface $orderTransferRepository,
    ) {}

    public function __invoke(GetOrderTransfersCommand $command): GetOrderTransfersResponse
    {
        $orderUuid = Uuid::create($command->orderId);

        $this->orderRepository->findByUuid($orderUuid)
            ?? throw OrderNotFoundException::withId($command->orderId);

        $transfers = $this->orderTransferRepository->findByOrderId($orderUuid);

        $items = array_map(static fn ($t): array => [
            'id' => $t->id()->value(),
            'order_id' => $t->orderId()->value(),
            'from_table_id' => $t->fromTableId()->value(),
            'to_table_id' => $t->toTableId()->value(),
            'transferred_by_user_id' => $t->transferredByUserId()->value(),
            'transferred_at' => $t->transferredAt()->format(DateTimeInterface::ATOM),
        ], $transfers);

        return GetOrderTransfersResponse::create($items);
    }
}
