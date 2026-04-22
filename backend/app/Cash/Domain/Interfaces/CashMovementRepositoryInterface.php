<?php

declare(strict_types=1);

namespace App\Cash\Domain\Interfaces;

use App\Cash\Domain\Entity\CashMovement;
use App\Shared\Domain\ValueObject\Uuid;

interface CashMovementRepositoryInterface
{
    public function save(CashMovement $cashMovement): void;

    public function getById(string $id): ?CashMovement;

    public function findById(Uuid $id): ?CashMovement;

    public function findByUuid(Uuid $uuid): ?CashMovement;

    public function findByCashSessionId(Uuid $cashSessionId): array;

    public function delete(Uuid $id): void;
}
