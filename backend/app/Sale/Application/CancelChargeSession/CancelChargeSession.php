<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelChargeSession;

use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

final class CancelChargeSession
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
    ) {}

    public function __invoke(
        string $chargeSessionId,
        string $cancelledByUserId,
        ?string $reason = null,
    ): CancelChargeSessionResponse {
        $sessionUuid = Uuid::create($chargeSessionId);
        $userUuid = Uuid::create($cancelledByUserId);

        $session = $this->chargeSessionRepository->findById($sessionUuid);

        if ($session === null) {
            throw new \DomainException('Charge session not found');
        }

        if (! $session->status()->isActive()) {
            throw new \DomainException(
                'Cannot cancel charge session: status is '.$session->status()->value()
            );
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

        $session->cancel($userUuid, $reason);

        $this->chargeSessionRepository->save($session);

        return CancelChargeSessionResponse::fromEntity($session, $paidCents, $paidDinerNumbers, $warningMessage);
    }
}
