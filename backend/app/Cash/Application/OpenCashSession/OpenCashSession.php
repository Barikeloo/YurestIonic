<?php

declare(strict_types=1);

namespace App\Cash\Application\OpenCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class OpenCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $deviceId,
        string $openedByUserId,
        int $initialAmountCents,
        ?string $notes = null,
    ): OpenCashSessionResponse {
        $restaurantUuid = Uuid::create($restaurantId);

        $activeSession = $this->cashSessionRepository->findActiveByDeviceId($deviceId, $restaurantUuid);
        if ($activeSession !== null) {
            throw new \DomainException('An active cash session already exists for this device.');
        }

        $cashSession = \App\Cash\Domain\Entity\CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: $restaurantUuid,
            deviceId: $deviceId,
            openedByUserId: Uuid::create($openedByUserId),
            initialAmount: Money::create($initialAmountCents),
            notes: $notes,
        );

        $this->cashSessionRepository->save($cashSession);

        return OpenCashSessionResponse::create($cashSession);
    }
}
