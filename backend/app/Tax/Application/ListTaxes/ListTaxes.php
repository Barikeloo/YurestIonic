<?php

namespace App\Tax\Application\ListTaxes;

use App\Tax\Domain\Interfaces\TaxRepositoryInterface;

class ListTaxes
{
    public function __construct(
        private TaxRepositoryInterface $taxRepository,
    ) {}

    /**
     * @return array<int, array<string, int|string>>
     */
    public function __invoke(bool $includeDeleted = false): array
    {
        $taxes = $this->taxRepository->findAll($includeDeleted);

        return array_map(
            static fn ($tax): array => ListTaxesItemResponse::create($tax)->toArray(),
            $taxes,
        );
    }
}
