<?php

declare(strict_types=1);

namespace App\Cash\Application\CancelClosingCashSession;

use App\Cash\Domain\Event\CashSessionClosingCancelled;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelClosingCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function __invoke(CancelClosingCashSessionCommand $command): CancelClosingCashSessionResponse
    {
        $cashSession = $this->cashSessionRepository->findByUuid(Uuid::create($command->cashSessionId))
            ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

        $beforeStatus = $cashSession->status()->value();
        $cashSession->cancelClosing();
        $this->cashSessionRepository->save($cashSession);

        $this->eventBus->publish(new CashSessionClosingCancelled(
            cashSessionId: $cashSession->id()->value(),
            statusBefore: $beforeStatus,
            statusAfter: $cashSession->status()->value(),
        ));

        return CancelClosingCashSessionResponse::create(
            id: $cashSession->id()->value(),
            status: $cashSession->status()->value(),
        );
    }
}
