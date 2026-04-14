<?php

namespace App\Tables\Application\UpdateTable;

use App\Tables\Domain\Interfaces\TableRepositoryInterface;
use App\Tables\Domain\ValueObject\TableName;
use App\Tables\Domain\ValueObject\ZoneId;
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

        $zoneIdVO = ZoneId::create($zoneId);

        // Validar que no exista otra mesa con el mismo nombre en esta zona (excluyendo la mesa actual)
        $existingTable = $this->tableRepository->findByZoneIdAndName($zoneIdVO, $name, $id);
        if ($existingTable !== null) {
            throw new InvalidArgumentException('Ya existe una mesa con ese nombre en esta zona.');
        }

        $table->update(
            $zoneIdVO,
            TableName::create($name),
        );

        $this->tableRepository->save($table);

        return UpdateTableResponse::create($table);
    }
}
