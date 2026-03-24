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
    public function __invoke(): array
    {
        $zones = $this->zoneRepository->findAll();

        return array_map(
            static fn ($zone): array => ListZonesItemResponse::create($zone)->toArray(),
            $zones,
        );
    }
}
