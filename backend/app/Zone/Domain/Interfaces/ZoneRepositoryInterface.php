<?php

namespace App\Zone\Domain\Interfaces;

use App\Zone\Domain\Entity\Zone;

interface ZoneRepositoryInterface
{
    public function save(Zone $zone): void;

    public function findById(string $id): ?Zone;

    /**
     * @return array<int, Zone>
     */
    public function findAll(): array;

    public function deleteById(string $id): bool;
}
