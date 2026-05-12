<?php

declare(strict_types=1);

namespace App\Cash\Application\CloseCashSession;

use App\Cash\Application\GenerateZReport\GenerateZReport;
use App\Cash\Application\GenerateZReport\GenerateZReportCommand;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\PendingSalesPreventClosingException;
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

    public function __invoke(CloseCashSessionCommand $command): CloseCashSessionResponse
    {
        return $this->transactionManager->run(function () use ($command) {
            $cashSessionUuid = Uuid::create($command->cashSessionId);

            $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid)
                ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

            $sales = $this->saleRepository->findByCashSessionId($cashSessionUuid);
            foreach ($sales as $sale) {
                if ($sale->status()->isPending()) {
                    throw new PendingSalesPreventClosingException;
                }
            }

            $finalAmount = Money::create($command->finalAmountCents);

            $zReportResponse = ($this->generateZReport)(new GenerateZReportCommand(
                cashSessionId: $command->cashSessionId,
                finalAmountCents: $command->finalAmountCents,
            ));

            $discrepancy = Money::create($zReportResponse->discrepancyCents);
            $expectedAmount = $finalAmount->subtract($discrepancy);

            $cashSession->close(
                closedByUserId: Uuid::create($command->closedByUserId),
                finalAmount: $finalAmount,
                expectedAmount: $expectedAmount,
                discrepancy: $discrepancy,
                zReportNumber: ZReportNumber::create($zReportResponse->reportNumber),
                zReportHash: ZReportHash::create($zReportResponse->reportHash),
                discrepancyReason: $command->discrepancyReason,
            );

            $this->cashSessionRepository->save($cashSession);

            return CloseCashSessionResponse::create(
                id: $cashSession->id()->value(),
                uuid: $cashSession->uuid()->value(),
                restaurantId: $cashSession->restaurantId()->value(),
                deviceId: $cashSession->deviceId()->value(),
                openedByUserId: $cashSession->openedByUserId()->value(),
                closedByUserId: $cashSession->closedByUserId()?->value(),
                openedAt: $cashSession->openedAt()->format('Y-m-d H:i:s'),
                closedAt: $cashSession->closedAt()?->format('Y-m-d H:i:s'),
                initialAmountCents: $cashSession->initialAmount()->toCents(),
                finalAmountCents: $finalAmount->toCents(),
                expectedAmountCents: $expectedAmount->toCents(),
                discrepancyCents: $discrepancy->toCents(),
                discrepancyReason: $cashSession->discrepancyReason(),
                zReportNumber: $zReportResponse->reportNumber,
                zReportHash: $zReportResponse->reportHash,
                status: $cashSession->status()->value(),
                zReport: $zReportResponse,
            );
        });
    }
}
