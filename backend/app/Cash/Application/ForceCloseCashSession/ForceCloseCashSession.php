<?php

declare(strict_types=1);

namespace App\Cash\Application\ForceCloseCashSession;

use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ForceCloseCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(ForceCloseCashSessionCommand $command): ForceCloseCashSessionResponse
    {
        $cashSessionUuid = Uuid::create($command->cashSessionId);

        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid)
            ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

        $cashSession->forceClose(Uuid::create($command->closedByUserId));
        $this->cashSessionRepository->save($cashSession);

        return ForceCloseCashSessionResponse::create(
            id: $cashSession->id()->value(),
            uuid: $cashSession->uuid()->value(),
            restaurantId: $cashSession->restaurantId()->value(),
            deviceId: $cashSession->deviceId()->value(),
            openedByUserId: $cashSession->openedByUserId()->value(),
            closedByUserId: $cashSession->closedByUserId()?->value(),
            openedAt: $cashSession->openedAt()->format('Y-m-d H:i:s'),
            closedAt: $cashSession->closedAt()?->format('Y-m-d H:i:s'),
            initialAmountCents: $cashSession->initialAmount()->toCents(),
            status: $cashSession->status()->value(),
        );
    }
}
