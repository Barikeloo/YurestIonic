<?php

declare(strict_types=1);

namespace App\Cash\Application\GetActiveCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Domain\ValueObject\Uuid;

final class GetActiveCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(GetActiveCashSessionCommand $command): ?GetActiveCashSessionResponse
    {
        $cashSession = $this->cashSessionRepository->findActiveByDeviceId(
            DeviceId::create($command->deviceId),
            Uuid::create($command->restaurantId),
        );

        if ($cashSession === null) {
            return null;
        }

        return GetActiveCashSessionResponse::create(
            id: $cashSession->id()->value(),
            uuid: $cashSession->uuid()->value(),
            restaurantId: $cashSession->restaurantId()->value(),
            deviceId: $cashSession->deviceId()->value(),
            openedByUserId: $cashSession->openedByUserId()->value(),
            openedAt: $cashSession->openedAt()->format('Y-m-d H:i:s'),
            initialAmountCents: $cashSession->initialAmount()->toCents(),
            status: $cashSession->status()->value(),
            notes: $cashSession->notes(),
        );
    }
}
