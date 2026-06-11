<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Interfaces;

interface ReportExportStorageInterface
{
    /**
     * Persists the raw file contents and returns the storage path.
     */
    public function store(int $restaurantId, string $uuid, string $extension, string $contents): string;

    /**
     * Returns the raw file contents, or null when the file is missing.
     */
    public function read(string $path): ?string;
}
