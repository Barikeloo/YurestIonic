<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Entity\SalePayment;
use App\Sale\Domain\Interfaces\ChargeSessionLineAssignmentRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleLineRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

final class ChargeSessionResponseBuilder
{
    public function __construct(
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly SaleLineRepositoryInterface $saleLineRepository,
        private readonly ChargeSessionLineAssignmentRepositoryInterface $assignmentRepository,
    ) {}

    public function build(ChargeSession $session): CreateChargeSessionResponse
    {
        [$totalCents, $paidCents, $paidDinerNumbers] = $this->collect($session);

        return CreateChargeSessionResponse::fromLiveDebt(
            $session,
            $totalCents,
            $paidCents,
            $paidDinerNumbers,
            $this->collectLineAssignments($session),
            $this->collectPaidOrderLineIds($session),
        );
    }

    public function collect(ChargeSession $session): array
    {
        $orderUuid = $session->orderId();

        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);
        $totalCents = 0;
        foreach ($orderLines as $line) {
            $totalCents += $line->price()->value() * $line->quantity()->value();
        }

        $paidCents = 0;
        $activeSaleIds = [];
        foreach ($this->saleRepository->findAllByOrderId($orderUuid) as $sale) {
            if ($sale->isCancelled()) {
                continue;
            }
            $activeSaleIds[$sale->uuid()->value()] = true;
            foreach ($this->salePaymentRepository->findBySaleId($sale->uuid()) as $payment) {
                /** @var SalePayment $payment */
                $paidCents += $payment->amount()->toCents();
            }
        }

        $paidDinerNumbers = [];
        foreach ($this->salePaymentRepository->findByChargeSessionId($session->id()) as $payment) {
            /** @var SalePayment $payment */
            if ($payment->dinerNumber() === null) {
                continue;
            }
            if (! isset($activeSaleIds[$payment->saleId()->value()])) {
                continue;
            }
            $paidDinerNumbers[] = $payment->dinerNumber();
        }

        return [$totalCents, $paidCents, array_values(array_unique($paidDinerNumbers))];
    }

    /**
     * @return array<int, array{order_line_id: string, diner_number: int}>
     */
    public function collectLineAssignments(ChargeSession $session): array
    {
        $assignments = $this->assignmentRepository->findBySessionId($session->id());

        return array_map(static fn ($a): array => [
            'order_line_id' => $a->orderLineId()->value(),
            'diner_number' => $a->dinerNumber(),
        ], $assignments);
    }

    /**
     * IDs de order_lines de la order de la sesión que ya están en alguna sale —
     * el front las debe descartar del pool de pendientes.
     *
     * @return array<int, string>
     */
    public function collectPaidOrderLineIds(ChargeSession $session): array
    {
        $orderUuid = $session->orderId();
        $paid = [];
        foreach ($this->saleRepository->findAllByOrderId($orderUuid) as $sale) {
            if ($sale->isCancelled()) {
                continue;
            }
            foreach ($this->saleLineRepository->findBySaleId($sale->uuid()) as $saleLine) {
                $paid[$saleLine->orderLineId()->value()] = true;
            }
        }

        return array_keys($paid);
    }
}
