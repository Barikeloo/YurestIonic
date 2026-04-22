<?php

declare(strict_types=1);

namespace App\Cash\Application\GetLastClosedCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetLastClosedCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
    ): GetLastClosedCashSessionResponse {
        $restaurantUuid = Uuid::create($restaurantId);

        $lastClosed = $this->cashSessionRepository->findLastClosedByRestaurant($restaurantUuid);
        $orphanSession = $this->cashSessionRepository->findOrphanByRestaurant($restaurantUuid);

        return GetLastClosedCashSessionResponse::create($lastClosed, $orphanSession);
    }
}
