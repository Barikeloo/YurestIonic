<?php

namespace App\Tax\Domain\Interfaces;

use App\Tax\Domain\Entity\Tax;

interface TaxRepositoryInterface
{
    public function save(Tax $tax): void;

    public function findById(string $id): ?Tax;

    /**
     * @return array<int, Tax>
     */
    public function findAll(): array;

    public function deleteById(string $id): bool;
}
