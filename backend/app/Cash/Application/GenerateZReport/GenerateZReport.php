<?php

declare(strict_types=1);

namespace App\Cash\Application\GenerateZReport;

use App\Cash\Domain\Entity\ZReport;
use App\Cash\Domain\Exception\CashSessionCannotGenerateZReportException;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\ZReportAlreadyExistsException;
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

    public function __invoke(GenerateZReportCommand $command): GenerateZReportResponse
    {
        $cashSessionUuid = Uuid::create($command->cashSessionId);

        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid)
            ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

        if (! $cashSession->status()->isClosing() && ! $cashSession->status()->isClosed()) {
            throw CashSessionCannotGenerateZReportException::withStatus($cashSession->status()->value());
        }

        if ($this->zReportRepository->findByCashSessionId($cashSessionUuid) !== null) {
            throw ZReportAlreadyExistsException::create();
        }

        $finalAmount = $command->finalAmountCents !== null
            ? Money::create($command->finalAmountCents)
            : $cashSession->finalAmount();

        if ($finalAmount === null) {
            throw CashSessionCannotGenerateZReportException::finalAmountRequired();
        }

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

        $tips = $this->tipRepository->findByCashSessionId($cashSessionUuid);
        $totalTips = Money::zero();
        foreach ($tips as $tip) {
            $totalTips = $totalTips->add($tip->amount());
        }

        $totalSales = $totalCash->add($totalCard)->add($totalOther);

        $sales = $this->saleRepository->findByCashSessionId($cashSessionUuid);
        $salesCount = count($sales);
        $cancelledSalesCount = 0;
        foreach ($sales as $sale) {
            if ($sale->isCancelled()) {
                $cancelledSalesCount++;
            }
        }

        $expectedCash = $cashSession->initialAmount()
            ->add($totalCash)
            ->add($cashIn)
            ->subtract($cashOut);

        $discrepancy = $finalAmount->subtract($expectedCash);

        $reportNumber = $this->zReportRepository->nextReportNumber($cashSession->restaurantId());

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
