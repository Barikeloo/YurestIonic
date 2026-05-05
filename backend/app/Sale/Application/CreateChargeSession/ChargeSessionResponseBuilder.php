<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Entity\SalePayment;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;

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

    public function collect(ChargeSession $session): array
    {
        $orderUuid = $session->orderId();

        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);
        $totalCents = 0;
        foreach ($orderLines as $line) {
            $totalCents += $line->price()->value() * $line->quantity()->value();
        }

        $paidCents = 0;
        foreach ($this->saleRepository->findAllByOrderId($orderUuid) as $sale) {
            foreach ($this->salePaymentRepository->findBySaleId($sale->uuid()) as $payment) {
                /** @var SalePayment $payment */
                $paidCents += $payment->amount()->toCents();
            }
        }

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
