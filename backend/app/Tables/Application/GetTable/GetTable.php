<?php

namespace App\Tables\Application\GetTable;

use App\Tables\Domain\Interfaces\TableRepositoryInterface;

class GetTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $id): ?GetTableResponse
    {
        $table = $this->tableRepository->findById($id);

        if ($table === null) {
            return null;
        }

        return GetTableResponse::create($table);
    }
}
