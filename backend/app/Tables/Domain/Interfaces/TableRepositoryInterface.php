<?php

namespace App\Tables\Domain\Interfaces;

use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\ValueObject\ZoneId;

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
    public function findByZoneIdAndName(ZoneId $zoneId, string $name, ?string $excludeId = null): ?Table;
}
