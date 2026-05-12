<?php

declare(strict_types=1);

namespace App\Cash\Application\GetLastClosedCashSession;

use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;

final class GetLastClosedCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly SaleRepositoryInterface $saleRepository,
    ) {}

    public function __invoke(GetLastClosedCashSessionCommand $command): GetLastClosedCashSessionResponse
    {
        $restaurantUuid = Uuid::create($command->restaurantId);

        $lastClosed = $this->cashSessionRepository->findLastClosedByRestaurant($restaurantUuid);
        $orphanSession = $this->cashSessionRepository->findOrphanByRestaurant($restaurantUuid);

        return GetLastClosedCashSessionResponse::create(
            lastClosed: $lastClosed !== null ? $this->mapLastClosed($lastClosed) : null,
            orphanSession: $orphanSession !== null ? $this->mapOrphan($orphanSession) : null,
        );
    }

    private function mapLastClosed(CashSession $session): LastClosedCashSessionItemResponse
    {
        $operator = $this->userRepository->findById($session->openedByUserId()->value());
        $sales = $this->saleRepository->findByCashSessionId($session->uuid());

        return LastClosedCashSessionItemResponse::create(
            id: $session->id()->value(),
            openedByUserId: $session->openedByUserId()->value(),
            closedByUserId: $session->closedByUserId()?->value(),
            openedAt: $session->openedAt()->format('Y-m-d H:i:s'),
            closedAt: $session->closedAt()?->format('Y-m-d H:i:s'),
            finalAmountCents: $session->finalAmount()?->toCents(),
            discrepancyCents: $session->discrepancy()?->toCents(),
            discrepancyReason: $session->discrepancyReason(),
            zReportNumber: $session->zReportNumber()?->value(),
            operatorName: $operator?->name()->value(),
            tickets: count($sales),
            diners: 0,
        );
    }

    private function mapOrphan(CashSession $session): OrphanCashSessionItemResponse
    {
        return OrphanCashSessionItemResponse::create(
            id: $session->id()->value(),
            openedByUserId: $session->openedByUserId()->value(),
            openedAt: $session->openedAt()->format('Y-m-d H:i:s'),
            deviceId: $session->deviceId()->value(),
        );
    }
}
