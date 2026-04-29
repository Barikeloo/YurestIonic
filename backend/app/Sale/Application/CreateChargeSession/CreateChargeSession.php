<?php

declare(strict_types=1);

namespace App\Sale\Application\CreateChargeSession;

use App\Order\Domain\Interfaces\OrderLineRepositoryInterface;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Sale\Application\GetOrderPaidTotal\GetOrderPaidTotal;
use App\Sale\Domain\Entity\ChargeSession;
use App\Sale\Domain\Exception\OrderHasPartialPaymentsException;
use App\Sale\Domain\Interfaces\ChargeSessionRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Caso de uso: Crear o recuperar una sesión de cobro para pago a partes iguales.
 *
 * Según la especificación:
 * - Si existe sesión activa → la retorna (sin recalcular)
 * - Si no existe → crea nueva calculando cuota fija
 */
final class CreateChargeSession
{
    public function __construct(
        private readonly ChargeSessionRepositoryInterface $chargeSessionRepository,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly OrderLineRepositoryInterface $orderLineRepository,
        private readonly GetOrderPaidTotal $getOrderPaidTotal,
    ) {}

    public function __invoke(
        string $restaurantId,
        string $orderId,
        string $openedByUserId,
        ?int $dinersCount = null,
    ): CreateChargeSessionResponse {
        $orderUuid = Uuid::create($orderId);
        $restaurantUuid = Uuid::create($restaurantId);

        // 1. Buscar sesión activa existente
        $existingSession = $this->chargeSessionRepository->findActiveByOrderId($orderUuid);

        if ($existingSession !== null) {
            // Retornar sesión existente sin recalcular (regla crítica de la especificación)
            return CreateChargeSessionResponse::fromEntity($existingSession);
        }

        // 2. Bloquear creación si la orden ya tiene pagos parciales sin sesión asociada.
        //    Hoy no podemos atribuir esos pagos a comensales concretos, así que dividir
        //    a partes iguales el restante daría una cuota arbitraria. Cuando exista el
        //    flujo "por líneas" / "por comensal" que registre pagos en charge_session,
        //    este bloqueo debe relajarse para permitir el flujo mixto.
        $paidCents = ($this->getOrderPaidTotal)($orderId);
        if ($paidCents > 0) {
            throw new OrderHasPartialPaymentsException($paidCents);
        }

        // 3. Obtener datos de la orden
        $order = $this->orderRepository->findByUuid($orderUuid);

        if ($order === null) {
            throw new \DomainException('Order not found');
        }

        // Usar diners de la orden o el proporcionado
        $finalDinersCount = $dinersCount ?? $order->diners()->value() ?? 1;

        if ($finalDinersCount <= 0) {
            throw new \DomainException('Diners count must be greater than 0');
        }

        // 3. Calcular total de la orden sumando líneas
        $orderLines = $this->orderLineRepository->findByOrderId($orderUuid);
        $totalCents = 0;
        foreach ($orderLines as $line) {
            $totalCents += $line->price()->value() * $line->quantity()->value();
        }

        if ($totalCents <= 0) {
            throw new \DomainException('Order has no items or total is zero');
        }

        // 4. Crear nueva sesión
        $chargeSession = ChargeSession::dddCreate(
            Uuid::generate(),
            $restaurantUuid,
            $orderUuid,
            Uuid::create($openedByUserId),
            $finalDinersCount,
            $totalCents,
        );

        // 5. Persistir
        $this->chargeSessionRepository->save($chargeSession);

        return CreateChargeSessionResponse::fromEntity($chargeSession);
    }
}
