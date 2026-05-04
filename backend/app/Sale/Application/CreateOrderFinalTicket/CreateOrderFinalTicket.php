<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateOrderFinalTicket;

use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Domain\Entity\OrderFinalTicket;
use App\Sale\Domain\Interfaces\OrderFinalTicketRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateOrderFinalTicket
{
    public function __construct(
        private readonly OrderFinalTicketRepositoryInterface $orderFinalTicketRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
    ) {}

    public function __invoke(string $orderId, string $closedByUserId): CreateOrderFinalTicketResponse
    {
        $orderUuid = Uuid::create($orderId);

        $existing = $this->orderFinalTicketRepository->findByOrderId($orderUuid);
        if ($existing !== null) {
            return CreateOrderFinalTicketResponse::fromEntity($existing);
        }

        $order = $this->orderRepository->findByUuid($orderUuid);
        if ($order === null) {
            throw new \DomainException('Order not found');
        }

        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);
        $totalConsumedCents = 0;
        foreach ($orderLines as $line) {
            $totalConsumedCents += $line->price()->value() * $line->quantity()->value();
        }

        $totalPaidCents = 0;
        $paymentsSnapshot = [];
        foreach ($this->saleRepository->findAllByOrderId($orderUuid) as $sale) {
            foreach ($this->salePaymentRepository->findBySaleId($sale->uuid()) as $payment) {
                $amountCents = $payment->amount()->toCents();
                $totalPaidCents += $amountCents;
                $paymentsSnapshot[] = [
                    'payment_id' => $payment->id()->value(),
                    'sale_id' => $payment->saleId()->value(),
                    'method' => $payment->method()->value(),
                    'amount_cents' => $amountCents,
                    'diner_number' => $payment->dinerNumber(),
                    'paid_at' => $payment->createdAt()->format(\DateTimeInterface::ATOM),
                ];
            }
        }

        if ($totalPaidCents < $totalConsumedCents) {
            throw new \DomainException('Cannot create final ticket: remaining debt is not zero');
        }

        $ticketNumber = $this->orderFinalTicketRepository->nextTicketNumber($order->restaurantId());

        $ticket = OrderFinalTicket::dddCreate(
            id: Uuid::generate(),
            restaurantId: $order->restaurantId(),
            orderId: $order->uuid(),
            closedByUserId: Uuid::create($closedByUserId),
            ticketNumber: $ticketNumber,
            totalConsumedCents: $totalConsumedCents,
            totalPaidCents: $totalPaidCents,
            paymentsSnapshot: $paymentsSnapshot,
        );

        $this->orderFinalTicketRepository->save($ticket);

        return CreateOrderFinalTicketResponse::fromEntity($ticket);
    }
}
