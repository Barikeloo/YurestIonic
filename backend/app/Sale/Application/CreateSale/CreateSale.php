<?php

namespace App\Sale\Application\CreateSale;

use App\Cash\Domain\Entity\Tip;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\Interfaces\TipRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SaleLine;
use App\Sale\Domain\Entity\SalePayment;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\PaymentMethod;
use App\Sale\Domain\ValueObject\SaleLinePrice;
use App\Sale\Domain\ValueObject\SaleLineQuantity;
use App\Sale\Domain\ValueObject\SaleLineTaxPercentage;
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
        private readonly SaleLineRepositoryInterface $saleLineRepository,
        private readonly TipRepositoryInterface $tipRepository,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        string $closedByUserId,
        string $deviceId,
        array $payments,
        ?array $orderLineIds = null,
        bool $isPartialPayment = false,
        int $tipCents = 0,
        ?string $chargeSessionId = null,
    ): CreateSaleResponse {
        return $this->transactionManager->run(function () use (
            $restaurantId,
            $orderId,
            $openedByUserId,
            $closedByUserId,
            $deviceId,
            $payments,
            $orderLineIds,
            $isPartialPayment,
            $tipCents,
            $chargeSessionId,
        ) {
            $restaurantUuid = Uuid::create($restaurantId);
            $orderUuid = Uuid::create($orderId);

            $activeSession = $this->cashSessionRepository->findActiveByDeviceId(DeviceId::create($deviceId), $restaurantUuid);
            if ($activeSession === null) {
                throw new \DomainException('No active cash session for this device.');
            }

            // Filosofía de "deuda viva": cualquier importe vale, simplemente
            // reduce la deuda de la mesa. Si la sale no cubre el total de las
            // líneas, es parcial por definición.
            if ($chargeSessionId !== null) {
                $isPartialPayment = true;
            }

            $sale = Sale::dddCreate(
                id: Uuid::generate(),
                restaurantId: $restaurantUuid,
                orderId: $orderUuid,
                openedByUserId: Uuid::create($openedByUserId),
                cashSessionId: $activeSession->uuid(),
            );

            $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);

            if ($orderLineIds !== null && count($orderLineIds) > 0) {
                $lineIdSet = array_map(fn ($id) => Uuid::create($id), $orderLineIds);
                $orderLines = array_filter($orderLines, fn ($line) => in_array($line->uuid(), $lineIdSet, true));
            }

            $total = 0;
            foreach ($orderLines as $line) {
                $total += $line->price()->value() * $line->quantity()->value();
            }

            $paymentsTotal = 0;
            foreach ($payments as $payment) {
                $paymentsTotal += $payment['amount_cents'];
            }

            if ($isPartialPayment) {
                $total = $paymentsTotal;
            } elseif ($paymentsTotal !== $total + $tipCents) {
                throw new \DomainException(
                    sprintf('Payments total (%d) must equal sale total (%d) plus tip (%d).', $paymentsTotal, $total, $tipCents)
                );
            }

            $ticketNumber = $this->saleRepository->nextTicketNumber($restaurantUuid);

            $sale->close(
                closedByUserId: Uuid::create($closedByUserId),
                ticketNumber: SaleTicketNumber::create($ticketNumber),
                total: SaleTotal::create($total),
            );

            $this->saleRepository->save($sale);

            foreach ($orderLines as $line) {
                $saleLine = SaleLine::dddCreate(
                    id: Uuid::generate(),
                    restaurantId: $restaurantUuid,
                    saleId: $sale->uuid(),
                    orderLineId: $line->uuid(),
                    productId: $line->productId(),
                    userId: Uuid::create($closedByUserId),
                    quantity: SaleLineQuantity::create($line->quantity()->value()),
                    price: SaleLinePrice::create($line->price()->value()),
                    taxPercentage: SaleLineTaxPercentage::create($line->taxPercentage()->value()),
                );
                $this->saleLineRepository->save($saleLine);
            }

            $order = $this->orderRepository->findByUuid($orderUuid);
            if ($order !== null) {
                $originalTotal = 0;
                $allOrderLines = $this->orderLineRepository->findByOrderId($orderUuid);
                foreach ($allOrderLines as $line) {
                    $originalTotal += $line->price()->value() * $line->quantity()->value();
                }

                $totalPaid = $paymentsTotal;
                $allSales = $this->saleRepository->findAllByOrderId($orderUuid);
                foreach ($allSales as $existingSale) {
                    if ($existingSale->uuid()->value() === $sale->uuid()->value()) {
                        continue;
                    }
                    $salePayments = $this->salePaymentRepository->findBySaleId($existingSale->uuid());
                    foreach ($salePayments as $payment) {
                        $totalPaid += $payment->amount()->toCents();
                    }
                }

                // Solo cerrar la orden si NO es parte de un charge session
                // (en charge sessions, el cierre se gestiona externamente)
                if ($chargeSessionId === null && $totalPaid >= $originalTotal) {
                    $order->close(Uuid::create($closedByUserId));
                }
                $this->orderRepository->save($order);
            }

            $chargeSessionUuid = $chargeSessionId !== null ? Uuid::create($chargeSessionId) : null;

            foreach ($payments as $payment) {
                $dinerNumber = isset($payment['diner_number']) ? (int) $payment['diner_number'] : null;

                $salePayment = SalePayment::dddCreate(
                    id: Uuid::generate(),
                    restaurantId: $restaurantUuid,
                    saleId: $sale->uuid(),
                    cashSessionId: $activeSession->uuid(),
                    method: PaymentMethod::create($payment['method']),
                    amount: Money::create($payment['amount_cents']),
                    userId: Uuid::create($closedByUserId),
                    metadata: $payment['metadata'] ?? null,
                    chargeSessionId: $chargeSessionUuid,
                    dinerNumber: $dinerNumber,
                );
                $this->salePaymentRepository->save($salePayment);
            }

            if ($tipCents > 0) {
                $tip = Tip::dddCreate(
                    id: Uuid::generate(),
                    restaurantId: $restaurantUuid,
                    saleId: $sale->uuid(),
                    cashSessionId: $activeSession->uuid(),
                    amount: Money::create($tipCents),
                    source: 'cash_declared',
                    beneficiaryUserId: null,
                );
                $this->tipRepository->save($tip);
            }

            return CreateSaleResponse::create($sale);
        });
    }
}
