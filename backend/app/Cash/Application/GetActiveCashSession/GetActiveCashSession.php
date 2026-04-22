<?php

declare(strict_types=1);

namespace App\Cash\Application\GetActiveCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class GetActiveCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $deviceId,
    ): ?GetActiveCashSessionResponse {
        $restaurantUuid = Uuid::create($restaurantId);
        $cashSession = $this->cashSessionRepository->findActiveByDeviceId($deviceId, $restaurantUuid);

        if ($cashSession === null) {
            return null;
        }

        return GetActiveCashSessionResponse::create($cashSession);
    }
}
