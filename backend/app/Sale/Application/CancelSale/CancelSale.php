<?php

namespace App\Sale\Application\CancelSale;

use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Domain\Entity\Sale;
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
    ) {}

    public function __invoke(
        string $saleId,
        string $cancelledByUserId,
        string $reason,
    ): CancelSaleResponse {
        $saleUuid = Uuid::create($saleId);
        $sale = $this->saleRepository->findByUuid($saleUuid);

        if ($sale === null) {
            throw new \DomainException('Sale not found.');
        }

        if ($sale->isCancelled()) {
            throw new \DomainException('Sale is already cancelled.');
        }

        $cancelledByUuid = Uuid::create($cancelledByUserId);

        // 1. Cancelar la venta
        $sale->cancel(
            cancelledByUserId: $cancelledByUuid,
            reason: $reason,
        );

        $this->saleRepository->save($sale);

        // 2. Reabrir la orden asociada
        $order = $this->orderRepository->findByUuid($sale->orderId());
        if ($order !== null) {
            $order->reopen($cancelledByUuid);
            $this->orderRepository->save($order);
        }

        // 3. Eliminar los pagos asociados
        $payments = $this->salePaymentRepository->findBySaleId($sale->id());
        foreach ($payments as $payment) {
            $this->salePaymentRepository->delete($payment->id());
        }

        // 4. Registrar movimiento de caja compensatorio (cash out)
        $cashSessionId = $sale->cashSessionId();
        if ($cashSessionId !== null) {
            $totalCash = 0;
            foreach ($payments as $payment) {
                if ($payment->method()->isCash()) {
                    $totalCash += $payment->amount()->toCents();
                }
            }

            if ($totalCash > 0) {
                $cashMovement = CashMovement::dddCreate(
                    id: Uuid::generate(),
                    restaurantId: $sale->restaurantId(),
                    cashSessionId: $cashSessionId,
                    type: MovementType::out(),
                    reasonCode: MovementReasonCode::cancellation(),
                    amount: Money::create($totalCash),
                    userId: $cancelledByUuid,
                    description: 'Devolución por cancelación de venta: ' . $reason,
                );
                $this->cashMovementRepository->save($cashMovement);
            }
        }

        return CancelSaleResponse::create($sale);
    }
}
