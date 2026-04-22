<?php

declare(strict_types=1);

namespace App\Cash\Application\GenerateZReport;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\TipRepositoryInterface;
use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Cash\Domain\Entity\ZReport;
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

    public function __invoke(string $cashSessionId): GenerateZReportResponse
    {
        $cashSessionUuid = Uuid::create($cashSessionId);
        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

        if ($cashSession === null) {
            throw new \DomainException('Cash session not found.');
        }

        // Calculate totals by payment method
        $payments = $this->salePaymentRepository->findByCashSessionId($cashSessionUuid);
        $totalCash = Money::create(0);
        $totalCard = Money::create(0);
        $totalOther = Money::create(0);

        foreach ($payments as $payment) {
            $amount = $payment->amount();
            switch ($payment->method()) {
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

        // Calculate cash movements
        $movements = $this->cashMovementRepository->findByCashSessionId($cashSessionUuid);
        $cashIn = Money::create(0);
        $cashOut = Money::create(0);

        foreach ($movements as $movement) {
            if ($movement->type()->value() === 'in') {
                $cashIn = $cashIn->add($movement->amount());
            } else {
                $cashOut = $cashOut->add($movement->amount());
            }
        }

        // Calculate tips
        $tips = $this->tipRepository->findByCashSessionId($cashSessionUuid);
        $totalTips = Money::create(0);

        foreach ($tips as $tip) {
            $totalTips = $totalTips->add($tip->amount());
        }

        // Calculate total sales
        $totalSales = $totalCash->add($totalCard)->add($totalOther);

        // Calculate sales count
        $sales = $this->saleRepository->findByCashSessionId($cashSessionUuid);
        $salesCount = count($sales);
        $cancelledSalesCount = 0;

        foreach ($sales as $sale) {
            if ($sale->status() === 'cancelled') {
                $cancelledSalesCount++;
            }
        }

        // Calculate discrepancy
        $expectedFinal = $cashSession->initialAmount()
            ->add($totalCash)
            ->add($cashIn)
            ->subtract($cashOut)
            ->add($totalTips);

        $finalAmount = $cashSession->finalAmount() ?? Money::create(0);
        $discrepancyCents = abs($finalAmount->toCents() - $expectedFinal->toCents());
        $discrepancy = Money::create($discrepancyCents);

        // Generate report number
        $reportNumber = $this->zReportRepository->nextReportNumber($cashSession->restaurantId()->value());

        // Generate Z-Report
        $zReport = ZReport::generate(
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
