<?php

declare(strict_types=1);

namespace App\Cash\Application\GenerateZReport;

use App\Cash\Domain\Entity\ZReport;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\Interfaces\TipRepositoryInterface;
use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class GenerateZReport
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
        private readonly TipRepositoryInterface $tipRepository,
        private readonly ZReportRepositoryInterface $zReportRepository,
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(
        string $cashSessionId,
        ?Money $finalAmountOverride = null,
    ): GenerateZReportResponse {
        $cashSessionUuid = Uuid::create($cashSessionId);
        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

        if ($cashSession === null) {
            throw new \DomainException('Cash session not found.');
        }

        // State guard: Z can only be generated on a session that is closing or already closed.
        if (! $cashSession->status()->isClosing() && ! $cashSession->status()->isClosed()) {
            throw new \DomainException(
                'Cannot generate Z-Report on a session with status ' . $cashSession->status()->value() . '.',
            );
        }

        // Idempotency guard: a session has at most one Z.
        if ($this->zReportRepository->findByCashSessionId($cashSessionUuid) !== null) {
            throw new \DomainException('A Z-Report already exists for this cash session.');
        }

        // Final amount: either provided (in-flight close) or taken from an already-closed session.
        $finalAmount = $finalAmountOverride ?? $cashSession->finalAmount();
        if ($finalAmount === null) {
            throw new \DomainException('Final amount is required to generate the Z-Report.');
        }

        // 1. Totals by payment method (only non-cancelled sales count for Z-Report).
        $payments = $this->salePaymentRepository->findNonCancelledByCashSessionId($cashSessionUuid);
        $totalCash = Money::zero();
        $totalCard = Money::zero();
        $totalOther = Money::zero();

        foreach ($payments as $payment) {
            $amount = $payment->amount();
            switch ($payment->method()->value()) {
                case 'cash':
                    $totalCash = $totalCash->add($amount);
                    break;
                case 'card':
                    $totalCard = $totalCard->add($amount);
                    break;
                default:
                    $totalOther = $totalOther->add($amount);
                    break;
            }
        }

        // 2. Manual cash movements (entradas/salidas).
        $movements = $this->cashMovementRepository->findByCashSessionId($cashSessionUuid);
        $cashIn = Money::zero();
        $cashOut = Money::zero();

        foreach ($movements as $movement) {
            if ($movement->type()->isIn()) {
                $cashIn = $cashIn->add($movement->amount());
            } else {
                $cashOut = $cashOut->add($movement->amount());
            }
        }

        // 3. Tips (informational; do not mix into cash reconciliation).
        $tips = $this->tipRepository->findByCashSessionId($cashSessionUuid);
        $totalTips = Money::zero();
        foreach ($tips as $tip) {
            $totalTips = $totalTips->add($tip->amount());
        }

        // 4. Total sales = sum of all payments, any method.
        $totalSales = $totalCash->add($totalCard)->add($totalOther);

        // 5. Sale counts.
        $sales = $this->saleRepository->findByCashSessionId($cashSessionUuid);
        $salesCount = count($sales);
        $cancelledSalesCount = 0;
        foreach ($sales as $sale) {
            if ($sale->isCancelled()) {
                $cancelledSalesCount++;
            }
        }

        // 6. Teorico efectivo = fondo + cash_payments + cash_in - cash_out
        //    Propinas card_added no tocan el efectivo (están en el TPV bancario).
        //    Propinas cash_declared deben haberse materializado como CashMovement(type=in) y ya están en cashIn.
        $expectedCash = $cashSession->initialAmount()
            ->add($totalCash)
            ->add($cashIn)
            ->subtract($cashOut);

        // 7. Discrepancia con signo: contado - teorico. Positivo = sobrante, negativo = faltante.
        $discrepancy = $finalAmount->subtract($expectedCash);

        // 8. Numeracion correlativa por restaurante.
        $reportNumber = $this->zReportRepository->nextReportNumber($cashSession->restaurantId());

        // 9. Build and persist Z.
        $zReport = ZReport::generate(
            restaurantId: $cashSession->restaurantId(),
            cashSessionId: $cashSessionUuid,
            reportNumber: $reportNumber,
            totalSales: $totalSales,
            totalCash: $totalCash,
            totalCard: $totalCard,
            totalOther: $totalOther,
            cashIn: $cashIn,
            cashOut: $cashOut,
            tips: $totalTips,
            discrepancy: $discrepancy,
            salesCount: $salesCount,
            cancelledSalesCount: $cancelledSalesCount,
        );

        $this->zReportRepository->save($zReport);

        return GenerateZReportResponse::create($zReport);
    }
}
