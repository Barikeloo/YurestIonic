<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ReadModel;

use App\Reporting\Application\Shared\DateRange;

interface DashboardReadRepositoryInterface
{
    public function getDashboardData(int $restaurantId, DateRange $range): array;
}
