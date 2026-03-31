<?php

namespace App\Zone\Application\ListZones;

use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;

class ListZones
{
    public function __construct(
        private ZoneRepositoryInterface $zoneRepository,
    ) {}

    /**
     * @return array<int, array<string, string>>
     */
    public function __invoke(bool $includeDeleted = false): array
    {
        $zones = $this->zoneRepository->findAll($includeDeleted);

        return array_map(
            static fn ($zone): array => ListZonesItemResponse::create($zone)->toArray(),
            $zones,
        );
    }
}
