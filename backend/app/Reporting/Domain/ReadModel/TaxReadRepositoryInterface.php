<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ReadModel;

use App\Reporting\Application\Shared\DateRange;

interface TaxReadRepositoryInterface
{
    public function getTaxReport(int $restaurantId, DateRange $range, DateRange $qRange, string $quarter, int $year): array;
}
