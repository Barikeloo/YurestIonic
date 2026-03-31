<?php

namespace App\Tables\Application\ListTables;

use App\Tables\Domain\Interfaces\TableRepositoryInterface;

class ListTables
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    /**
     * @return array<int, array<string, string>>
     */
    public function __invoke(bool $includeDeleted = false): array
    {
        $tables = $this->tableRepository->findAll($includeDeleted);

        return array_map(
            static fn ($table): array => ListTablesItemResponse::create($table)->toArray(),
            $tables,
        );
    }
}
