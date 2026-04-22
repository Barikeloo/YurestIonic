<?php

namespace App\Sale\Application\CreateSale;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\PaymentMethod;
use App\Shared\Domain\ValueObject\Uuid;

final class CreateSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        string $closedByUserId,
        string $deviceId,
        array $payments,
    ): CreateSaleResponse {
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

        $ticketNumber = $this->saleRepository->nextTicketNumber($restaurantId);

        $sale->close(
            closedByUserId: Uuid::create($closedByUserId),
            ticketNumber: SaleTicketNumber::create($ticketNumber),
            total: SaleTotal::create($total),
        );

        $this->saleRepository->save($sale);

        foreach ($payments as $payment) {
            $salePayment = \App\Cash\Domain\Entity\SalePayment::dddCreate(
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
    }
}
