<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Entity\SalePayment;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

/**
 * Construye el response de una ChargeSession aplicando la filosofía de
 * "deuda viva": cada lectura recalcula total y pagado a partir del estado
 * actual del order, no de un snapshot. Esto permite:
 *  - Que pagos hechos antes de abrir la sesión cuenten (no llevan tag).
 *  - Que comandas añadidas tras abrir la sesión suban la deuda.
 *
 * `total_cents` = suma de líneas actuales del order.
 * `paid_cents` = suma de TODOS los SalePayments del order, sin filtrar tag.
 * `paid_diner_numbers` = solo pagos con tag de la sesión + diner_number,
 *   son etiquetas visuales para el ticket / UX.
 */
final class ChargeSessionResponseBuilder
{
    public function __construct(
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function build(ChargeSession $session): CreateChargeSessionResponse
    {
        [$totalCents, $paidCents, $paidDinerNumbers] = $this->collect($session);

        return CreateChargeSessionResponse::fromLiveDebt(
            $session,
            $totalCents,
            $paidCents,
            $paidDinerNumbers,
        );
    }

    /**
     * @return array{0: int, 1: int, 2: array<int>}
     */
    public function collect(ChargeSession $session): array
    {
        $orderUuid = $session->orderId();

        // total_cents dinámico: si llegan nuevas comandas, la deuda sube.
        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);
        $totalCents = 0;
        foreach ($orderLines as $line) {
            $totalCents += $line->price()->value() * $line->quantity()->value();
        }

        // paid_cents dinámico: cuenta TODOS los pagos del order, incluso los
        // que se registraron antes de abrir la sesión (sin tag).
        $paidCents = 0;
        foreach ($this->saleRepository->findAllByOrderId($orderUuid) as $sale) {
            foreach ($this->salePaymentRepository->findBySaleId($sale->uuid()) as $payment) {
                /** @var SalePayment $payment */
                $paidCents += $payment->amount()->toCents();
            }
        }

        // paid_diner_numbers: solo etiquetas visuales de pagos tagueados.
        $paidDinerNumbers = [];
        foreach ($this->salePaymentRepository->findByChargeSessionId($session->id()) as $payment) {
            /** @var SalePayment $payment */
            if ($payment->dinerNumber() !== null) {
                $paidDinerNumbers[] = $payment->dinerNumber();
            }
        }

        return [$totalCents, $paidCents, array_values(array_unique($paidDinerNumbers))];
    }
}
