<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelChargeSession;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Exception\ChargeSessionNotActiveException;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelChargeSession
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly AuditRecorderInterface $auditRecorder,
    ) {}

    public function __invoke(CancelChargeSessionCommand $command): CancelChargeSessionResponse
    {
        $sessionUuid = Uuid::create($command->chargeSessionId);
        $userUuid = Uuid::create($command->cancelledByUserId);

        $session = $this->chargeSessionRepository->findById($sessionUuid)
            ?? throw ChargeSessionNotFoundException::withId($command->chargeSessionId);

        if (! $session->status()->isActive()) {
            throw ChargeSessionNotActiveException::create();
        }

        [$totalCents, $paidCents, $paidDinerNumbers] = $this->responseBuilder->collect($session);
        $paidCount = count($paidDinerNumbers);
        unset($totalCents);

        $warningMessage = null;
        if ($paidCount > 0) {
            $warningMessage = "ATENCIÓN: Hay {$paidCount} pago(s) completado(s) ".
                'por un total de '.number_format($paidCents / 100, 2).' €. '.
                'Se requiere devolución manual al cliente.';
        }

        $session->cancel($userUuid, $command->reason);

        $this->chargeSessionRepository->save($session);

        $this->auditRecorder->record(new AuditEventDraft(
            restaurantId: $session->restaurantId(),
            slug: ActionSlug::create('sale.charge_session_cancelled'),
            entityType: 'charge_session',
            entityId: $session->id()->value(),
            userId: $userUuid,
            deviceId: $command->deviceId,
            ipAddress: $command->ipAddress,
            reason: $command->reason,
            before: ['status' => 'active'],
            after: ['status' => 'cancelled'],
            metadata: [
                'paid_formatted' => $paidCents > 0 ? number_format($paidCents / 100, 2).' €' : null,
                'paid_diners_count' => count($paidDinerNumbers),
            ],
        ));

        return CancelChargeSessionResponse::fromEntity($session, $paidCents, $paidDinerNumbers, $warningMessage);
    }
}
