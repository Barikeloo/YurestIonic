<?php

declare(strict_types=1);

namespace App\Reporting\Application\Shared;

interface ReportFileGeneratorInterface
{
    public function generate(int $restaurantId, string $type, string $format, DateRange $range, ?string $quarter = null, ?int $year = null): array;
}
