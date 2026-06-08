<?php

declare(strict_types=1);

namespace App\Sale\Domain\Interfaces;

use App\Sale\Domain\Entity\ChargeSessionLineAssignment;
use App\Shared\Domain\ValueObject\Uuid;

interface ChargeSessionLineAssignmentRepositoryInterface
{

    public function findBySessionId(Uuid $chargeSessionId): array;

    public function replaceForSession(Uuid $chargeSessionId, array $assignments): void;

    public function deleteByOrderLineIds(Uuid $chargeSessionId, array $orderLineIds): void;
}
