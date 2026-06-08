<?php

namespace App\Tax\Domain\Interfaces;

use App\Tax\Domain\Entity\Tax;

interface TaxRepositoryInterface
{
    public function save(Tax $tax): void;

    public function existsByName(string $name): bool;

    public function findById(string $id): ?Tax;

    public function findAll(bool $includeDeleted = false): array;

    public function deleteById(string $id): bool;
}
