<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Interfaces;

interface ReportExportStorageInterface
{
    public function store(int $restaurantId, string $uuid, string $extension, string $contents): string;

    public function read(string $path): ?string;
}
