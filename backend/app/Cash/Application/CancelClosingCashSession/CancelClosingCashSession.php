<?php

declare(strict_types=1);

namespace App\Cash\Application\CancelClosingCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelClosingCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(
        string $cashSessionId,
    ): void {
        $cashSessionUuid = Uuid::create($cashSessionId);
        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

        if ($cashSession === null) {
            throw new \DomainException('Cash session not found.');
        }

        $cashSession->cancelClosing();
        $this->cashSessionRepository->save($cashSession);
    }
}
