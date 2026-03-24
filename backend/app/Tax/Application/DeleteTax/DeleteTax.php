<?php

namespace App\Tax\Application\DeleteTax;

use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class DeleteTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $id): bool
    {
        return $this->taxRepository->deleteById($id);
    }
}
