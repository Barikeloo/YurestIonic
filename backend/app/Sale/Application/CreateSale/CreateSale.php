<?php

namespace App\Sale\Application\CreateSale;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
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

            // Filter order lines if orderLineIds is provided (split bill)
            if ($orderLineIds !== null && count($orderLineIds) > 0) {
                $lineIdSet = array_map(fn($id) => Uuid::create($id), $orderLineIds);
                $orderLines = array_filter($orderLines, fn($line) => in_array($line->uuid(), $lineIdSet, true));
            }

            $total = 0;
            foreach ($orderLines as $line) {
                $total += $line->price()->value() * $line->quantity()->value();
            }

            $paymentsTotal = 0;
            foreach ($payments as $payment) {
                $paymentsTotal += $payment['amount_cents'];
            }

            // For partial payments, use payment amount as sale total
            // For full payments, require payments to be at least total (allow tips)
            if ($isPartialPayment) {
                $total = $paymentsTotal;
            } elseif ($paymentsTotal < $total) {
                throw new \DomainException('Payments total is less than sale total.');
            }

            $ticketNumber = $this->saleRepository->nextTicketNumber($restaurantUuid);

            $sale->close(
                closedByUserId: Uuid::create($closedByUserId),
                ticketNumber: SaleTicketNumber::create($ticketNumber),
                total: SaleTotal::create($total),
            );

            $this->saleRepository->save($sale);

            // Create sale lines for the selected order lines
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

            // Close the order (status=invoiced)
            $order = $this->orderRepository->findByUuid($orderUuid);
            if ($order !== null) {
                if ($isPartialPayment) {
                    // Partial payment - check if payment is complete
                    // Calculate original order total
                    $originalTotal = 0;
                    $allOrderLines = $this->orderLineRepository->findByOrderId($orderUuid);
                    foreach ($allOrderLines as $line) {
                        $originalTotal += $line->price()->value() * $line->quantity()->value();
                    }

                    // Calculate total paid by summing all sale payments
                    $allSales = $this->saleRepository->findAllByOrderId($orderUuid);
                    $totalPaid = 0;
                    foreach ($allSales as $sale) {
                        // Get all payments for this sale
                        $salePayments = $this->salePaymentRepository->findBySaleId($sale->uuid());
                        foreach ($salePayments as $payment) {
                            $totalPaid += $payment->amount()->toCents();
                        }
                    }
                    // Add current payment
                    $totalPaid += $paymentsTotal;

                    error_log('Partial payment - Total paid: ' . $totalPaid . ', Original total: ' . $originalTotal);

                    if ($totalPaid >= $originalTotal) {
                        // Payment complete, close the order
                        $order->close(Uuid::create($closedByUserId));
                        error_log('Order closed (payment complete): ' . $orderUuid->value());
                    } else {
                        error_log('Partial payment - order remains open: ' . $orderUuid->value());
                    }
                } elseif ($orderLineIds === null) {
                    // Full payment (no split), close the order
                    $order->close(Uuid::create($closedByUserId));
                    error_log('Order closed (full payment): ' . $orderUuid->value());
                } else {
                    // Split payment, close only if all lines are paid
                    $allOrderLines = $this->orderLineRepository->findByOrderId($orderUuid);
                    $paidLineIds = array_map(fn($line) => $line->uuid()->value(), $orderLines);
                    $unpaidLines = array_filter($allOrderLines, fn($line) => !in_array($line->uuid()->value(), $paidLineIds, true));

                    error_log('Split payment - unpaid lines: ' . count($unpaidLines));

                    if (count($unpaidLines) === 0) {
                        // All lines paid, close the order
                        $order->close(Uuid::create($closedByUserId));
                        error_log('Order closed (all lines paid): ' . $orderUuid->value());
                    }
                }
                $this->orderRepository->save($order);
                error_log('Order saved with status: ' . $order->status()->value());
            } else {
                error_log('Order not found: ' . $orderUuid->value());
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
