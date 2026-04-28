<?php

declare(strict_types=1);

namespace App\Sale\Application\RecordChargeSessionPayment;

use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Caso de uso: Registrar un pago de un comensal en una sesión de cobro.
 *
 * Según la especificación:
 * - El importe siempre es la cuota fija de la sesión
 * - El último comensal paga el resto (redondeo)
 * - No se permite pagar si la sesión no está activa
 * - No se permite pagar dos veces el mismo comensal
 */
final class RecordChargeSessionPayment
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
    ) {}

    public function __invoke(
        string $chargeSessionId,
        int $dinerNumber,
        string $paymentMethod,
    ): RecordChargeSessionPaymentResponse {
        $sessionUuid = Uuid::create($chargeSessionId);

        // 1. Buscar sesión
        $session = $this->chargeSessionRepository->findById($sessionUuid);

        if ($session === null) {
            throw new \DomainException('Charge session not found');
        }

        if (! $session->status()->isActive()) {
            throw new \DomainException('Charge session is not active');
        }

        // 2. Verificar que el comensal existe en esta sesión
        if ($dinerNumber < 1 || $dinerNumber > $session->dinersCount()) {
            throw new \DomainException('Invalid diner number');
        }

        // 3. Verificar que no haya pagado ya
        foreach ($session->payments() as $payment) {
            if ($payment->dinerNumber() === $dinerNumber && $payment->isCompleted()) {
                throw new \DomainException("Diner {$dinerNumber} has already paid");
            }
        }

        // 4. Registrar pago
        $payment = $session->recordPayment(
            Uuid::generate(),
            $dinerNumber,
            $paymentMethod
        );

        // 5. Persistir
        $this->chargeSessionRepository->save($session);

        return RecordChargeSessionPaymentResponse::fromEntities($session, $payment);
    }
}
