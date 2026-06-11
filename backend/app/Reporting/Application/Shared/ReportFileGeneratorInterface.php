<?php

declare(strict_types=1);

namespace App\Reporting\Application\Shared;

interface ReportFileGeneratorInterface
{
    /**
     * @return array{filename: string, mimeType: string, contents: string}
     */
    public function generate(int $restaurantId, string $type, string $format, DateRange $range, ?string $quarter = null, ?int $year = null): array;
}
