<?php

namespace App\Tax\Application\GetTax;

use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class GetTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $id): ?GetTaxResponse
    {
        $tax = $this->taxRepository->findById($id);

        if ($tax === null) {
            return null;
        }

        return GetTaxResponse::create($tax);
    }
}
