<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Persistence;

use App\Reporting\Domain\Interfaces\ReportExportStorageInterface;
use Illuminate\Support\Facades\Storage;

final class LocalReportExportStorage implements ReportExportStorageInterface
{
    private const DISK = 'local';

    public function store(int $restaurantId, string $uuid, string $extension, string $contents): string
    {
        $path = "report-exports/{$restaurantId}/{$uuid}.{$extension}";

        Storage::disk(self::DISK)->put($path, $contents);

        return $path;
    }

    public function read(string $path): ?string
    {
        if (! Storage::disk(self::DISK)->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->get($path);
    }
}
