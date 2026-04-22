<?php

declare(strict_types=1);

namespace App\Cash\Application\CloseCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Application\GenerateZReport\GenerateZReport;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class CloseCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly GenerateZReport $generateZReport,
    ) {}

    public function __invoke(
        string $cashSessionId,
        string $closedByUserId,
        int $finalAmountCents,
        int $expectedAmountCents,
        int $discrepancyCents,
        ?string $discrepancyReason = null,
    ): CloseCashSessionResponse {
        $cashSessionUuid = Uuid::create($cashSessionId);
        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

        if ($cashSession === null) {
            throw new \DomainException('Cash session not found.');
        }

        $closedByUserUuid = Uuid::create($closedByUserId);

        $cashSession->close(
            closedByUserId: $closedByUserUuid,
            finalAmount: Money::create($finalAmountCents),
            expectedAmount: Money::create($expectedAmountCents),
            discrepancy: Money::create($discrepancyCents),
            discrepancyReason: $discrepancyReason,
        );

        $this->cashSessionRepository->save($cashSession);

        $zReportResponse = ($this->generateZReport)($cashSessionUuid);

        return CloseCashSessionResponse::create($cashSession, $zReportResponse);
    }
}
