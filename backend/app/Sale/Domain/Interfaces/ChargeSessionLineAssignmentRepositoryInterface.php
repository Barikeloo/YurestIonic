<?php

declare(strict_types=1);

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\ChargeSessionLineAssignment;
use App\Shared\Domain\ValueObject\Uuid;

interface ChargeSessionLineAssignmentRepositoryInterface
{
    /**
     * @return array<int, ChargeSessionLineAssignment>
     */
    public function findBySessionId(Uuid $chargeSessionId): array;

    /**
     * Reemplaza atómicamente el conjunto de asignaciones de la sesión.
     *
     * @param  array<int, ChargeSessionLineAssignment>  $assignments
     */
    public function replaceForSession(Uuid $chargeSessionId, array $assignments): void;

    /**
     * Borra las asignaciones de un subconjunto de order_lines dentro de la sesión.
     * Se usa al cobrar para liberar las líneas ya facturadas.
     *
     * @param  array<int, Uuid>  $orderLineIds
     */
    public function deleteByOrderLineIds(Uuid $chargeSessionId, array $orderLineIds): void;
}
