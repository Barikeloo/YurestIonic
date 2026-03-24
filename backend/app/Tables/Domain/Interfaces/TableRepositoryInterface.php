<?php

namespace App\Tables\Domain\Interfaces;

use App\Tables\Domain\Entity\Table;

interface TableRepositoryInterface
{
    public function save(Table $table): void;

    public function findById(string $id): ?Table;

    /**
     * @return array<int, Table>
     */
    public function findAll(): array;

    public function deleteById(string $id): bool;
}
