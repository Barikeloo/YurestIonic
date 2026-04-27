<?php

namespace App\Tables\Domain\Interfaces;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Entity\Table;

interface TableRepositoryInterface
{
    public function save(Table $table): void;

    public function findById(string $id): ?Table;

    /**
     * @return array<int, Table>
     */
    public function findAll(bool $includeDeleted = false): array;

    public function deleteById(string $id): bool;

    /**
     * Find a table by zone ID and name (case-insensitive).
     */
    public function findByZoneIdAndName(Uuid $zoneId, string $name, ?string $excludeId = null): ?Table;
}
