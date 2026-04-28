<?php

declare(strict_types=1);

namespace App\Sale\Application\UpdateChargeSessionDiners;

use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Caso de uso: Modificar el número de comensales en una sesión de cobro.
 *
 * Según la especificación:
 * - Solo permitido si no hay pagos registrados (paidCount === 0)
 * - Recalcula la cuota con el nuevo número
 * - Bloqueado con mensaje claro si hay pagos
 */
final class UpdateChargeSessionDiners
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
    ) {}

    public function __invoke(
        string $chargeSessionId,
        int $newDinersCount,
    ): UpdateChargeSessionDinersResponse {
        if ($newDinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        $sessionUuid = Uuid::create($chargeSessionId);

        // 1. Buscar sesión
        $session = $this->chargeSessionRepository->findById($sessionUuid);

        if ($session === null) {
            throw new \DomainException('Charge session not found');
        }

        // 2. Verificar que se puede editar (no hay pagos)
        if (! $session->canEditDinersCount()) {
            $paidCount = $session->paidDinersCount();
            throw new \DomainException(
                "Ya hay {$paidCount} pago(s) registrado(s). ".
                'No es posible modificar el número de comensales. '.
                'Si necesitas ajustar el cobro, cancela la sesión y vuelve a abrirla.'
            );
        }

        // 3. Actualizar número de comensales (recalcula cuota automáticamente)
        $session->updateDinersCount($newDinersCount);

        // 4. Persistir
        $this->chargeSessionRepository->save($session);

        return UpdateChargeSessionDinersResponse::fromEntity($session);
    }
}
