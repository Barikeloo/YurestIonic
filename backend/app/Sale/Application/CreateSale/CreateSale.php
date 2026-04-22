<?php

namespace App\Sale\Application\CreateSale;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SalePayment;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\PaymentMethod;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        string $closedByUserId,
        string $deviceId,
        array $payments,
    ): CreateSaleResponse {
        return $this->transactionManager->run(function () use (
            $restaurantId,
            $orderId,
            $openedByUserId,
            $closedByUserId,
            $deviceId,
            $payments,
        ) {
            $restaurantUuid = Uuid::create($restaurantId);
            $orderUuid = Uuid::create($orderId);

            $activeSession = $this->cashSessionRepository->findActiveByDeviceId($deviceId, $restaurantUuid);
            if ($activeSession === null) {
                throw new \DomainException('No active cash session for this device.');
            }

            $sale = Sale::dddCreate(
                id: Uuid::generate(),
                restaurantId: $restaurantUuid,
                orderId: $orderUuid,
                openedByUserId: Uuid::create($openedByUserId),
                cashSessionId: $activeSession->uuid(),
            );

            $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);

            $total = 0;
            foreach ($orderLines as $line) {
                $total += $line->price()->value() * $line->quantity()->value();
            }

            $paymentsTotal = 0;
            foreach ($payments as $payment) {
                $paymentsTotal += $payment['amount_cents'];
            }

            if ($paymentsTotal !== $total) {
                throw new \DomainException('Payments total does not match sale total.');
            }

            $ticketNumber = $this->saleRepository->nextTicketNumber($restaurantUuid);

            $sale->close(
                closedByUserId: Uuid::create($closedByUserId),
                ticketNumber: SaleTicketNumber::create($ticketNumber),
                total: SaleTotal::create($total),
            );

            $this->saleRepository->save($sale);

            // Close the order (status=invoiced) after creating the sale
            $order = $this->orderRepository->findByUuid($orderUuid);
            if ($order !== null) {
                $order->close(Uuid::create($closedByUserId));
                $this->orderRepository->save($order);
            }

            foreach ($payments as $payment) {
                $salePayment = SalePayment::dddCreate(
                    id: Uuid::generate(),
                    restaurantId: $restaurantUuid,
                    saleId: $sale->uuid(),
                    cashSessionId: $activeSession->uuid(),
                    method: PaymentMethod::create($payment['method']),
                    amount: Money::create($payment['amount_cents']),
                    userId: Uuid::create($closedByUserId),
                    metadata: $payment['metadata'] ?? null,
                );
                $this->salePaymentRepository->save($salePayment);
            }

            return CreateSaleResponse::create($sale);
        });
    }
}
