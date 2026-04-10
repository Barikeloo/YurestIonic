<?php

namespace App\Tax\Application\UpdateTax;

use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class UpdateTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $id, ?string $name = null, ?int $percentage = null): ?UpdateTaxResponse
    {
        $tax = $this->taxRepository->findById($id);

        if ($tax === null) {
            return null;
        }

        $tax->update(
            $name !== null ? TaxName::create($name) : null, // : null replace to : $tax->name; $tax->name
            $percentage !== null ? TaxPercentage::create($percentage) : null // : null replace to : $tax->percentage
        );
        $this->taxRepository->save($tax);

        return UpdateTaxResponse::create($tax);
    }
}
