<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelChargeSession;

use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Event\ChargeSessionCancelled;
use App\Sale\Domain\Exception\ChargeSessionNotActiveException;
use App\Sale\Domain\Exception\ChargeSessionNotFoundException;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelChargeSession
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
        private readonly EventBusInterface $eventBus,
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

        $this->eventBus->publish(new ChargeSessionCancelled(
            chargeSessionId: $session->id()->value(),
            paidFormatted: $paidCents > 0 ? number_format($paidCents / 100, 2).' €' : null,
            paidDinersCount: count($paidDinerNumbers),
            reason: $command->reason ?? '',
        ));

        return CancelChargeSessionResponse::fromEntity($session, $paidCents, $paidDinerNumbers, $warningMessage);
    }
}
