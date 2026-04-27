<?php

namespace App\Tables\Application\GetTable;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;

class GetTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $id): ?GetTableResponse
    {
        $tableId = Uuid::create($id);
        $table = $this->tableRepository->findById($tableId->value());

        if ($table === null) {
            return null;
        }

        return GetTableResponse::create($table);
    }
}
