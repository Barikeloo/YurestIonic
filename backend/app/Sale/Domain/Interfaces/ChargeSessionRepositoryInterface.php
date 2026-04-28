<?php

declare(strict_types=1);

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\ChargeSession;
use App\Shared\Domain\ValueObject\Uuid;

interface ChargeSessionRepositoryInterface
{
    /**
     * Guardar una sesión de cobro
     */
    public function save(ChargeSession $chargeSession): void;

    /**
     * Buscar sesión por ID
     */
    public function findById(Uuid $id): ?ChargeSession;

    /**
     * Buscar sesión activa por ID de orden
     */
    public function findActiveByOrderId(Uuid $orderId): ?ChargeSession;

    /**
     * Buscar todas las sesiones de una orden
     *
     * @return array<ChargeSession>
     */
    public function findByOrderId(Uuid $orderId): array;

    /**
     * Eliminar sesión (soft delete)
     */
    public function delete(Uuid $id): void;
}
