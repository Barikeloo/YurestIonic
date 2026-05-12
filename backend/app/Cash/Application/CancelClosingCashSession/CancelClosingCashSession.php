<?php

declare(strict_types=1);

namespace App\Cash\Application\CancelClosingCashSession;

use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelClosingCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(CancelClosingCashSessionCommand $command): CancelClosingCashSessionResponse
    {
        $cashSession = $this->cashSessionRepository->findByUuid(Uuid::create($command->cashSessionId))
            ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

        $cashSession->cancelClosing();
        $this->cashSessionRepository->save($cashSession);

        return CancelClosingCashSessionResponse::create(
            id: $cashSession->id()->value(),
            status: $cashSession->status()->value(),
        );
    }
}
