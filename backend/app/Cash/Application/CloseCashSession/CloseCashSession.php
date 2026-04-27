<?php

declare(strict_types=1);

namespace App\Cash\Application\CloseCashSession;

use App\Cash\Application\GenerateZReport\GenerateZReport;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\ZReportHash;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class CloseCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly GenerateZReport $generateZReport,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(
        string $cashSessionId,
        string $closedByUserId,
        int $finalAmountCents,
        ?string $discrepancyReason = null,
    ): CloseCashSessionResponse {
        return $this->transactionManager->run(function () use (
            $cashSessionId,
            $closedByUserId,
            $finalAmountCents,
            $discrepancyReason,
        ) {
            $cashSessionUuid = Uuid::create($cashSessionId);
            $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

            if ($cashSession === null) {
                throw new \DomainException('Cash session not found.');
            }

            // Pre-check: no sales with pending status
            $sales = $this->saleRepository->findByCashSessionId($cashSessionUuid);
            foreach ($sales as $sale) {
                if ($sale->status()->isPending()) {
                    throw new \DomainException('Cannot close cash session with pending sales.');
                }
            }

            $finalAmount = Money::create($finalAmountCents);

            // 1. Generate the Z-Report first. It computes totals, teorico and signed discrepancy
            //    server-side and is the source of truth. If the session is not in 'closing'
            //    state, GenerateZReport will reject.
            $zReportResponse = ($this->generateZReport)($cashSessionId, $finalAmount);

            // Expected is derived algebraically so this use case does not depend on the
            // internal expected-cash formula in GenerateZReport.
            $discrepancy = Money::create($zReportResponse->discrepancyCents);
            $expectedAmount = $finalAmount->subtract($discrepancy);

            // 2. Apply the close on the session with the numbers from the Z.
            $cashSession->close(
                closedByUserId: Uuid::create($closedByUserId),
                finalAmount: $finalAmount,
                expectedAmount: $expectedAmount,
                discrepancy: $discrepancy,
                zReportNumber: ZReportNumber::create($zReportResponse->reportNumber),
                zReportHash: ZReportHash::create($zReportResponse->reportHash),
                discrepancyReason: $discrepancyReason,
            );

            $this->cashSessionRepository->save($cashSession);

            return CloseCashSessionResponse::create($cashSession, $zReportResponse);
        });
    }
}
