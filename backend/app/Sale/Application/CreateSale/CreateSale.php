<?php

namespace App\Sale\Application\CreateSale;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        string $closedByUserId,
    ): CreateSaleResponse {
        $sale = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            orderId: Uuid::create($orderId),
            openedByUserId: Uuid::create($openedByUserId),
        );

        $orderLines = $this->orderLineRepository->findByOrderId(Uuid::create($orderId));

        $total = 0;
        foreach ($orderLines as $line) {
            $total += $line->price()->value() * $line->quantity()->value();
        }

        $ticketNumber = $this->saleRepository->nextTicketNumber($restaurantId);

        $sale->close(
            closedByUserId: Uuid::create($closedByUserId),
            ticketNumber: SaleTicketNumber::create($ticketNumber),
            total: SaleTotal::create($total),
        );

        $this->saleRepository->save($sale);

        return CreateSaleResponse::create($sale);
    }
}
