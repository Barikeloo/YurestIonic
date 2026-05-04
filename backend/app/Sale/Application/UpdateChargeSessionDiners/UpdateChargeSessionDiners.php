<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

use App\Sale\Application\CreateChargeSession\ChargeSessionResponseBuilder;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Caso de uso: Modificar el número de comensales en una sesión de cobro.
 *
 * Filosofía "Comensales mutables": el número de comensales puede cambiar en
 * cualquier momento, incluso después de pagos registrados. La entidad solo
 * impide bajar el contador por debajo de los comensales que ya marcaron pago
 * (no se pueden "borrar" pagos retroactivamente).
 */
final class UpdateChargeSessionDiners
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly ChargeSessionResponseBuilder $responseBuilder,
    ) {}

    public function __invoke(
        string $chargeSessionId,
        int $newDinersCount,
    ): UpdateChargeSessionDinersResponse {
        if ($newDinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        $sessionUuid = Uuid::create($chargeSessionId);

        $session = $this->chargeSessionRepository->findById($sessionUuid);

        if ($session === null) {
            throw new \DomainException('Charge session not found');
        }

        [$totalCents, $paidCents, $paidDinerNumbers] = $this->responseBuilder->collect($session);

        // La entity valida que newDinersCount >= paidCount (no se puede bajar
        // por debajo de comensales que ya marcaron pago).
        $session->updateDinersCount($newDinersCount, count($paidDinerNumbers));

        $this->chargeSessionRepository->save($session);

        return UpdateChargeSessionDinersResponse::fromLiveDebt($session, $totalCents, $paidCents, $paidDinerNumbers);
    }
}
