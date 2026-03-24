<?php

namespace App\Tax\Application\CreateTax;

use App\Tax\Domain\Entity\Tax;
use App\Tax\Domain\Interfaces\TaxRepositoryInterface;
use App\Tax\Domain\ValueObject\TaxName;
use App\Tax\Domain\ValueObject\TaxPercentage;

class CreateTax
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    public function __invoke(string $name, int $percentage): CreateTaxResponse
    {
        $tax = Tax::dddCreate(TaxName::create($name), TaxPercentage::create($percentage));
        $this->taxRepository->save($tax);

        return CreateTaxResponse::create($tax);
    }
}
