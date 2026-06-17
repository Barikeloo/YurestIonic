<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ReadModel;

use App\Reporting\Application\Shared\DateRange;

interface EmployeesReadRepositoryInterface
{
    public function getEmployeesReport(int $restaurantId, DateRange $range): array;
}
