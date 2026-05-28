<?php

declare(strict_types=1);

namespace App\Cash\Application\OpenCashSession;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Exception\ActiveCashSessionAlreadyExistsException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;

final class OpenCashSession
{
    public function __construct(
        private readonly CashSessionRepositoryInterface $cashSessionRepository,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(OpenCashSessionCommand $command): OpenCashSessionResponse
    {
        $restaurantUuid = Uuid::create($command->restaurantId);
        $device = DeviceId::create($command->deviceId);

        if ($this->cashSessionRepository->findActiveByDeviceId($device, $restaurantUuid) !== null) {
            throw ActiveCashSessionAlreadyExistsException::forDevice($command->deviceId);
        }

        $cashSession = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: $restaurantUuid,
            deviceId: $device,
            openedByUserId: Uuid::create($command->openedByUserId),
            initialAmount: Money::create($command->initialAmountCents),
            notes: $command->notes,
        );

        $this->cashSessionRepository->save($cashSession);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $restaurantUuid,
            slug: ActionSlug::create('caja.opened'),
            entityType: 'cash_session',
            entityId: $cashSession->id()->value(),
            userId: Uuid::create($command->openedByUserId),
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            metadata: [
                'opening_float_formatted' => number_format($command->initialAmountCents / 100, 2).' €',
            ],
        ));

        return OpenCashSessionResponse::create(
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
