<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Interfaces;

interface ReportExportRepositoryInterface
{
    public function save(array $export): void;

    public function listRecent(int $restaurantId, int $days, int $limit): array;

    public function findForDownload(int $restaurantId, string $uuid): ?array;
}
