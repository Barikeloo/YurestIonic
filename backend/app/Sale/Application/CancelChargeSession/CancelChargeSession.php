<?php

declare(strict_types=1);

namespace App\Sale\Application\CancelChargeSession;

use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Caso de uso: Cancelar una sesión de cobro.
 *
 * Según la especificación:
 * - Cambia estado a 'cancelled'
 * - No elimina pagos ya registrados (trazabilidad)
 * - Alerta al camarero si hay pagos que requieren devolución manual
 */
final class CancelChargeSession
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
    ) {}

    public function __invoke(
        string $chargeSessionId,
        string $cancelledByUserId,
        ?string $reason = null,
    ): CancelChargeSessionResponse {
        $sessionUuid = Uuid::create($chargeSessionId);
        $userUuid = Uuid::create($cancelledByUserId);

        // 1. Buscar sesión
        $session = $this->chargeSessionRepository->findById($sessionUuid);

        if ($session === null) {
            throw new \DomainException('Charge session not found');
        }

        // 2. Verificar que está activa
        if (! $session->status()->isActive()) {
            throw new \DomainException(
                'Cannot cancel charge session: status is '.$session->status()->value()
            );
        }

        // 3. Preparar mensaje de advertencia si hay pagos
        $paidCount = $session->paidDinersCount();
        $warningMessage = null;
        if ($paidCount > 0) {
            $totalPaid = 0;
            foreach ($session->payments() as $payment) {
                if ($payment->isCompleted()) {
                    $totalPaid += $payment->amount();
                }
            }
            $warningMessage = "ATENCIÓN: Hay {$paidCount} pago(s) completado(s) ".
                'por un total de '.number_format($totalPaid / 100, 2).' €. '.
                'Se requiere devolución manual al cliente.';
        }

        // 4. Cancelar sesión
        $session->cancel($userUuid, $reason);

        // 5. Persistir
        $this->chargeSessionRepository->save($session);

        return CancelChargeSessionResponse::fromEntity($session, $warningMessage);
    }
}
