<?php

declare(strict_types=1);

namespace App\Cash\Application\StartClosingCashSession;

use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class StartClosingCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly TransactionManagerInterface $transactionManager,
    ) {}

    public function __invoke(
        string $cashSessionId,
    ): StartClosingCashSessionResponse {
        return $this->transactionManager->run(function () use ($cashSessionId) {
            $cashSessionUuid = Uuid::create($cashSessionId);
            $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid);

            if ($cashSession === null) {
                throw new \DomainException('Cash session not found.');
            }

            $cashSession->startClosing();
            $this->cashSessionRepository->save($cashSession);

            return StartClosingCashSessionResponse::create($cashSession);
        });
    }
}
