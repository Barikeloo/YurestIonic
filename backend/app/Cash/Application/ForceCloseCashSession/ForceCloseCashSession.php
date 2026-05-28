<?php

declare(strict_types=1);

namespace App\Cash\Application\ForceCloseCashSession;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class ForceCloseCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(ForceCloseCashSessionCommand $command): ForceCloseCashSessionResponse
    {
        $cashSessionUuid = Uuid::create($command->cashSessionId);

        $cashSession = $this->cashSessionRepository->findByUuid($cashSessionUuid)
            ?? throw CashSessionNotFoundException::withId($command->cashSessionId);

        $cashSession->forceClose(Uuid::create($command->closedByUserId));
        $this->cashSessionRepository->save($cashSession);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $cashSession->restaurantId(),
            slug: ActionSlug::create('caja.force_closed'),
            entityType: 'cash_session',
            entityId: $cashSession->id()->value(),
            userId: Uuid::create($command->closedByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'delta_final_formatted' => '0.00 €',
            ],
        ));

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
