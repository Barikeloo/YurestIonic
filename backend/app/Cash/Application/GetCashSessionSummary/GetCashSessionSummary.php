<?php

declare(strict_types=1);

namespace App\Cash\Application\GetCashSessionSummary;

use App\Cash\Domain\Exception\CashSessionNotFoundException;
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

    public function __invoke(GetCashSessionSummaryCommand $command): GetCashSessionSummaryResponse
    {
        $cashSessionUuid = Uuid::create($command->cashSessionId);

        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid)
            ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

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
            $uniqueSaleIds[$payment->saleId()->value()] = true;
        }

        return GetCashSessionSummaryResponse::create(
            id: $cashSession->id()->value(),
            uuid: $cashSession->uuid()->value(),
            status: $cashSession->status()->value(),
            initialAmountCents: $cashSession->initialAmount()->toCents(),
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
            ticketsCount: count($uniqueSaleIds),
            dinersCount: 0,
            tipsCard: 0,
        );
    }
}
