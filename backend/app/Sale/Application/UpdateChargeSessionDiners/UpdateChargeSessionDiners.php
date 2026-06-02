<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Exception\InvalidDinerCountException;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class UpdateChargeSessionDiners
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(UpdateChargeSessionDinersCommand $command): UpdateChargeSessionDinersResponse
    {
        if ($command->newDinersCount <= 0) {
            throw InvalidDinerCountException::create();
        }

        $sessionUuid = Uuid::create($command->chargeSessionId);

        $session = $this->chargeSessionRepository->findById($sessionUuid)
            ?? throw ChargeSessionNotFoundException::withId($command->chargeSessionId);

        [$totalCents, $paidCents, $paidDinerNumbers] = $this->responseBuilder->collect($session);
        $dinersBefore = $session->dinersCount();

        $session->updateDinersCount($command->newDinersCount, count($paidDinerNumbers));

        $this->chargeSessionRepository->save($session);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $session->restaurantId(),
            slug: ActionSlug::create('sale.diners_updated'),
            entityType: 'charge_session',
            entityId: $session->id()->value(),
            userId: null,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            before: ['diners_count' => $dinersBefore],
            after: ['diners_count' => $command->newDinersCount],
        ));

        return UpdateChargeSessionDinersResponse::fromLiveDebt($session, $totalCents, $paidCents, $paidDinerNumbers);
    }
}
