<?php

namespace App\Tables\Application\DeleteTable;

use App\Tables\Domain\Interfaces\TableRepositoryInterface;

class DeleteTable
{
    public function __construct(
        private TableRepositoryInterface $tableRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        return $this->tableRepository->deleteById($id);
    }
}
