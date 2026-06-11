<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Interfaces;

interface ReportExportRepositoryInterface
{
    public function save(array $export): void;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listRecent(int $restaurantId, int $days, int $limit): array;

    /**
     * @return array<string, mixed>|null
     */
    public function findForDownload(int $restaurantId, string $uuid): ?array;
}
