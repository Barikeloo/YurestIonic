<?php

declare(strict_types=1);

namespace App\GuestOrder\Domain\Interfaces;

use App\GuestOrder\Domain\ReadModel\CatalogReadModel;

interface GuestCatalogRepositoryInterface
{
    public function getCatalog(int $restaurantInternalId, int $catalogVersion): CatalogReadModel;

    public function getCatalogVersion(int $restaurantInternalId): int;
}
