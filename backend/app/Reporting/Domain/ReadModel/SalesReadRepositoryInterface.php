<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ReadModel;

use App\Reporting\Application\Shared\DateRange;

interface SalesReadRepositoryInterface
{
    public function getSalesList(int $restaurantId, DateRange $range, int $page, int $perPage): array;

    public function getSaleDetail(int $restaurantId, string $saleUuid): ?array;
}
