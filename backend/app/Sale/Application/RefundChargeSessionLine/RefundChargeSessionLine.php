<?php

declare(strict_types=1);

namespace App\Sale\Application\RefundChargeSessionLine;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Application\CreateChargeSession\CreateChargeSessionResponse;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\RefundablePaidLineNotFoundException;
use App\Sale\Domain\Interfaces\ChargeSessionLineAssignmentRepositoryInterface;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class RefundChargeSessionLine
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SaleLineRepositoryInterface $saleLineRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
        private readonly ChargeSessionLineAssignmentRepositoryInterface $assignmentRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(RefundChargeSessionLineCommand $command): CreateChargeSessionResponse
    {
        $sessionUuid = Uuid::create($command->chargeSessionId);
        $session = $this->chargeSessionRepository->findById($sessionUuid)
            ?? throw ChargeSessionNotFoundException::withId($command->chargeSessionId);

        $refundedByUuid = Uuid::create($command->refundedByUserId);
        $reason = $command->reason ?? 'Reembolso de línea';

        $sale = $this->findActiveSaleForOrderLine($session->orderId(), $command->orderLineId)
            ?? throw RefundablePaidLineNotFoundException::forLine(
                $command->orderLineId,
                $command->chargeSessionId,
            );

        $sale->cancel($refundedByUuid, $reason);
        $this->saleRepository->save($sale);

        $this->assignmentRepository->deleteByOrderLineIds(
            $sessionUuid,
            [Uuid::create($command->orderLineId)],
        );

        $payments = $this->salePaymentRepository->findBySaleId($sale->id());
        $cashTotal = 0;
        foreach ($payments as $payment) {
            if ($payment->method()->isCash()) {
                $cashTotal += $payment->amount()->toCents();
            }
            $this->salePaymentRepository->delete($payment->id());
        }

        if ($sale->cashSessionId() !== null && $cashTotal > 0) {
            $movement = CashMovement::dddCreate(
                id: Uuid::generate(),
                restaurantId: $sale->restaurantId(),
                cashSessionId: $sale->cashSessionId(),
                type: MovementType::out(),
                reasonCode: MovementReasonCode::cancellation(),
                amount: Money::create($cashTotal),
                userId: $refundedByUuid,
                description: 'Reembolso de línea: '.$reason,
            );
            $this->cashMovementRepository->save($movement);
        }

        $order = $this->orderRepository->findByUuid($sale->orderId());
        if ($order !== null && $order->status()->isInvoiced()) {
            $order->reopen($refundedByUuid);
            $this->orderRepository->save($order);
        }

        if (! $session->status()->isActive() && ! $session->status()->isCancelled()) {
            $session->reactivate();
            $this->chargeSessionRepository->save($session);
        }

        $session = $this->chargeSessionRepository->findById($sessionUuid)
            ?? throw ChargeSessionNotFoundException::withId($command->chargeSessionId);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $session->restaurantId(),
            slug: ActionSlug::create('sale.line_refunded'),
            entityType: 'order_line',
            entityId: $command->orderLineId,
            userId: $refundedByUuid,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            reason: $reason,
            metadata: [
                'charge_session_id' => $command->chargeSessionId,
                'cash_movement_cents' => $cashTotal,
            ],
        ));

        return $this->responseBuilder->build($session);
    }

    private function findActiveSaleForOrderLine(Uuid $orderId, string $orderLineId): ?Sale
    {
        foreach ($this->saleRepository->findAllByOrderId($orderId) as $sale) {
            if ($sale->isCancelled()) {
                continue;
            }
            foreach ($this->saleLineRepository->findBySaleId($sale->uuid()) as $saleLine) {
                if ($saleLine->orderLineId()->value() === $orderLineId) {
                    return $sale;
                }
            }
        }

        return null;
    }
}
