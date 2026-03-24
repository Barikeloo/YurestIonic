<?php

namespace App\Tables\Application\UpdateTable;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;

class UpdateTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $id, string $zoneId, string $name): ?UpdateTableResponse
    {
        $table = $this->tableRepository->findById($id);

        if ($table === null) {
            return null;
        }

        $table->update(
            Uuid::create($zoneId),
            TableName::create($name),
        );

        $this->tableRepository->save($table);

        return UpdateTableResponse::create($table);
    }
}
