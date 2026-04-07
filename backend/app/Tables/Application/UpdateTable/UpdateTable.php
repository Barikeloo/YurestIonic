<?php

namespace App\Tables\Application\UpdateTable;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;
use InvalidArgumentException;

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

        // Validar que no exista otra mesa con el mismo nombre en esta zona (excluyendo la mesa actual)
        $existingTable = $this->tableRepository->findByZoneIdAndName($zoneId, $name, $id);
        if ($existingTable !== null) {
            throw new InvalidArgumentException('Ya existe una mesa con ese nombre en esta zona.');
        }

        $table->update(
            Uuid::create($zoneId),
            TableName::create($name),
        );

        $this->tableRepository->save($table);

        return UpdateTableResponse::create($table);
    }
}
