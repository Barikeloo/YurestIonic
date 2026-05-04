<?php

declare(strict_types=1);

namespace App\Sale\Application\RecordChargeSessionPayment;

use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Application\CreateOrderFinalTicket\CreateOrderFinalTicket;
use App\Sale\Application\CreateSale\CreateSale;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Caso de uso: Registrar un pago dentro de una sesión de cobro.
 *
 * Filosofía "deuda viva":
 * - El pago pertenece a la mesa, no al comensal. `dinerNumber` es solo
 *   etiqueta visual para el ticket / UX.
 * - El importe es libre; si no se proporciona, se sugiere la cuota equitativa
 *   sobre la deuda restante.
 * - La sesión se cierra cuando la deuda viva llega a 0, no cuando todos los
 *   comensales han marcado pago.
 */
final class RecordChargeSessionPayment
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly CreateSale $createSale,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly CreateOrderFinalTicket $createOrderFinalTicket,
    ) {}

    public function __invoke(
        string $chargeSessionId,
        string $paymentMethod,
        string $openedByUserId,
        string $closedByUserId,
        string $deviceId,
        ?int $dinerNumber = null,
        ?int $amountCents = null,
    ): RecordChargeSessionPaymentResponse {
        $sessionUuid = Uuid::create($chargeSessionId);

        $session = $this->chargeSessionRepository->findById($sessionUuid);

        if ($session === null) {
            throw new \DomainException('Charge session not found');
        }

        if (! $session->status()->isActive()) {
            throw new \DomainException('Charge session is not active');
        }

        if ($dinerNumber !== null && ($dinerNumber < 1 || $dinerNumber > $session->dinersCount())) {
            throw new \DomainException('Invalid diner number');
        }

        [$totalCents, $paidCents, $paidDinerNumbers] = $this->responseBuilder->collect($session);

        $remainingCents = max(0, $totalCents - $paidCents);

        if ($remainingCents <= 0) {
            throw new \DomainException('Charge session has no remaining debt');
        }

        if ($amountCents === null) {
            $unpaidDinersCount = max(1, $session->dinersCount() - count($paidDinerNumbers));
            $amountCents = $unpaidDinersCount === 1
                ? $remainingCents
                : (int) floor($remainingCents / $unpaidDinersCount);
        }

        if ($amountCents <= 0) {
            throw new \DomainException('Payment amount must be greater than 0');
        }

        if ($amountCents > $remainingCents) {
            throw new \DomainException(
                "Payment amount ({$amountCents}) exceeds remaining debt ({$remainingCents})"
            );
        }

        $newPaidCents = $paidCents + $amountCents;
        $newRemainingCents = max(0, $totalCents - $newPaidCents);
        $isSessionComplete = $newRemainingCents === 0;

        $payment = [
            'method' => $paymentMethod,
            'amount_cents' => $amountCents,
            'snapshot_total_cents' => $totalCents,
            'snapshot_paid_cents' => $newPaidCents,
            'snapshot_remaining_cents' => $newRemainingCents,
        ];
        if ($dinerNumber !== null) {
            $payment['diner_number'] = $dinerNumber;
        }

        $saleResponse = ($this->createSale)(
            restaurantId: $session->restaurantId()->value(),
            orderId: $session->orderId()->value(),
            openedByUserId: $openedByUserId,
            closedByUserId: $closedByUserId,
            deviceId: $deviceId,
            payments: [$payment],
            chargeSessionId: $chargeSessionId,
        );

        $newPaidDinersCount = $dinerNumber !== null && ! in_array($dinerNumber, $paidDinerNumbers, true)
            ? count($paidDinerNumbers) + 1
            : count($paidDinerNumbers);

        if ($isSessionComplete) {
            $session->markCompleted();
            $this->chargeSessionRepository->save($session);

            $order = $this->orderRepository->findByUuid($session->orderId());
            if ($order !== null) {
                $order->close(Uuid::create($closedByUserId));
                $this->orderRepository->save($order);
            }

            ($this->createOrderFinalTicket)(
                orderId: $session->orderId()->value(),
                closedByUserId: $closedByUserId,
            );
        }

        return new RecordChargeSessionPaymentResponse(
            paymentId: $saleResponse->id,
            chargeSessionId: $chargeSessionId,
            dinerNumber: $dinerNumber,
            amountCents: $amountCents,
            paymentMethod: $paymentMethod,
            status: 'completed',
            sessionPaidDinersCount: $newPaidDinersCount,
            sessionStatus: $isSessionComplete ? 'completed' : 'active',
            sessionRemainingCents: $newRemainingCents,
            isSessionComplete: $isSessionComplete,
        );
    }
}
