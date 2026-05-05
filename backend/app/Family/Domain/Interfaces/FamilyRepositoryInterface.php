<?php

namespace App\Family\Domain\Interfaces;

use App\Family\Domain\Entity\Family;

interface FamilyRepositoryInterface
{
    public function save(Family $family): void;

    public function findById(string $id): ?Family;

    public function findAll(bool $includeDeleted = false): array;

    public function deleteById(string $id): bool;
}
