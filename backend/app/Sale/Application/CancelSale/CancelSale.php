<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelSale;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Domain\Exception\SaleAlreadyCancelledException;
use App\Sale\Domain\Exception\SaleNotFoundException;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelSale
{
    public function __construct(
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CancelSaleCommand $command): CancelSaleResponse
    {
        $sale = $this->saleRepository->findByUuid(Uuid::create($command->saleId))
            ?? throw SaleNotFoundException::withId($command->saleId);

        if ($sale->isCancelled()) {
            throw SaleAlreadyCancelledException::create();
        }

        $cancelledByUuid = Uuid::create($command->cancelledByUserId);

        $sale->cancel(
            cancelledByUserId: $cancelledByUuid,
            reason: $command->reason,
        );

        $this->saleRepository->save($sale);

        $order = $this->orderRepository->findByUuid($sale->orderId());
        if ($order !== null) {
            $order->reopen($cancelledByUuid);
            $this->orderRepository->save($order);
        }

        $payments = $this->salePaymentRepository->findBySaleId($sale->id());
        foreach ($payments as $payment) {
            $this->salePaymentRepository->delete($payment->id());
        }

        $cashRefundedCents = 0;
        $cashSessionId = $sale->cashSessionId();
        if ($cashSessionId !== null) {
            foreach ($payments as $payment) {
                if ($payment->method()->isCash()) {
                    $cashRefundedCents += $payment->amount()->toCents();
                }
            }

            if ($cashRefundedCents > 0) {
                $cashMovement = CashMovement::dddCreate(
                    id: Uuid::generate(),
                    restaurantId: $sale->restaurantId(),
                    cashSessionId: $cashSessionId,
                    type: MovementType::out(),
                    reasonCode: MovementReasonCode::cancellation(),
                    amount: Money::create($cashRefundedCents),
                    userId: $cancelledByUuid,
                    description: 'Devolución por cancelación de venta: '.$command->reason,
                );
                $this->cashMovementRepository->save($cashMovement);
            }
        }

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $sale->restaurantId(),
            slug: ActionSlug::create('sale.cancelled'),
            entityType: 'sale',
            entityId: $sale->id()->value(),
            userId: $cancelledByUuid,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            sessionId: $cashSessionId,
            reason: $command->reason,
            metadata: [
                'order_id' => $sale->orderId()->value(),
                'total_cents' => $sale->total()->value(),
                'cash_refunded_cents' => $cashRefundedCents,
                'payments_removed' => count($payments),
            ],
        ));

        return CancelSaleResponse::create($sale);
    }
}
