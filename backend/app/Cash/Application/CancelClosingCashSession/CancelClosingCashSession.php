<?php

declare(strict_types=1);

namespace App\Cash\Application\CancelClosingCashSession;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelClosingCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CancelClosingCashSessionCommand $command): CancelClosingCashSessionResponse
    {
        $cashSession = $this->cashSessionRepository->findByUuid(Uuid::create($command->cashSessionId))
            ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

        $beforeStatus = $cashSession->status()->value();
        $cashSession->cancelClosing();
        $this->cashSessionRepository->save($cashSession);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $cashSession->restaurantId(),
            slug: ActionSlug::create('caja.closing_cancelled'),
            entityType: 'cash_session',
            entityId: $cashSession->id()->value(),
            userId: null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: ['status' => $beforeStatus],
            after: ['status' => $cashSession->status()->value()],
        ));

        return CancelClosingCashSessionResponse::create(
            id: $cashSession->id()->value(),
            status: $cashSession->status()->value(),
        );
    }
}
