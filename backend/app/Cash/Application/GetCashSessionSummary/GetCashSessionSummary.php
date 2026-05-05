<?php

declare(strict_types=1);

namespace App\Cash\Application\GetCashSessionSummary;

use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetCashSessionSummary
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly CashMovementRepositoryInterface $cashMovementRepository,
        private readonly SalePaymentRepositoryInterface $salePaymentRepository,
    ) {}

    public function __invoke(
        string $cashSessionId,
    ): ?GetCashSessionSummaryResponse {
        $cashSessionUuid = Uuid::create($cashSessionId);
        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

        if ($cashSession === null) {
            return null;
        }

        $movements = $this->cashMovementRepository->findByCashSessionId($cashSessionUuid);
        $payments = $this->salePaymentRepository->findNonCancelledByCashSessionId($cashSessionUuid);

        $totalInMovements = 0;
        $totalOutMovements = 0;
        foreach ($movements as $movement) {
            if ($movement->type()->isIn()) {
                $totalInMovements += $movement->amount()->toCents();
            } else {
                $totalOutMovements += $movement->amount()->toCents();
            }
        }

        $totalSales = 0;
        $totalCashPayments = 0;
        $totalCardPayments = 0;
        $totalBizumPayments = 0;
        $totalOtherPayments = 0;

        foreach ($payments as $payment) {
            $totalSales += $payment->amount()->toCents();
            switch ($payment->method()->value()) {
                case 'cash':
                    $totalCashPayments += $payment->amount()->toCents();
                    break;
                case 'card':
                    $totalCardPayments += $payment->amount()->toCents();
                    break;
                case 'bizum':
                    $totalBizumPayments += $payment->amount()->toCents();
                    break;
                default:
                    $totalOtherPayments += $payment->amount()->toCents();
                    break;
            }
        }

        $expectedAmount = $cashSession->initialAmount()->toCents() + $totalCashPayments + $totalInMovements - $totalOutMovements;

        $uniqueSaleIds = [];
        foreach ($payments as $payment) {
            $saleId = $payment->saleId()->value();
            $uniqueSaleIds[$saleId] = true;
        }
        $ticketsCount = count($uniqueSaleIds);

        $dinersCount = 0;
        $tipsCard = 0;

        return GetCashSessionSummaryResponse::create(
            cashSession: $cashSession,
            totalSales: $totalSales,
            totalCashPayments: $totalCashPayments,
            totalCardPayments: $totalCardPayments,
            totalBizumPayments: $totalBizumPayments,
            totalOtherPayments: $totalOtherPayments,
            totalInMovements: $totalInMovements,
            totalOutMovements: $totalOutMovements,
            expectedAmount: $expectedAmount,
            movementsCount: count($movements),
            paymentsCount: count($payments),
            ticketsCount: $ticketsCount,
            dinersCount: $dinersCount,
            tipsCard: $tipsCard,
        );
    }
}
