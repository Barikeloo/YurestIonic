<?php

declare(strict_types=1);

namespace App\Cash\Application\ForceCloseCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ForceCloseCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
    ) {}

    public function __invoke(
        string $cashSessionId,
        string $closedByUserId,
    ): void {
        $cashSessionUuid = Uuid::create($cashSessionId);
        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

        if ($cashSession === null) {
            throw new \DomainException('Cash session not found.');
        }

        $closedByUserUuid = Uuid::create($closedByUserId);

        $cashSession->forceClose($closedByUserUuid);
        $this->cashSessionRepository->save($cashSession);
    }
}
