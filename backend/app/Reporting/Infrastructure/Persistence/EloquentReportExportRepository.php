<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Persistence;

use App\Reporting\Domain\Interfaces\ReportExportRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class EloquentReportExportRepository implements ReportExportRepositoryInterface
{
    public function save(array $export): void
    {
        $now = now();

        DB::table('report_exports')->insert([
            'uuid'          => $export['uuid'],
            'restaurant_id' => $export['restaurant_id'],
            'user_uuid'     => $export['user_uuid'],
            'user_name'     => $export['user_name'],
            'report_type'   => $export['report_type'],
            'title'         => $export['title'],
            'format'        => $export['format'],
            'filename'      => $export['filename'],
            'size_bytes'    => $export['size_bytes'],
            'storage_path'  => $export['storage_path'],
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }

    public function listRecent(int $restaurantId, int $days, int $limit): array
    {
        return DB::table('report_exports')
            ->where('restaurant_id', $restaurantId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get(['uuid', 'title', 'report_type', 'format', 'filename', 'size_bytes', 'user_name', 'created_at'])
            ->map(fn ($r) => [
                'uuid'        => $r->uuid,
                'title'       => $r->title,
                'report_type' => $r->report_type,
                'format'      => $r->format,
                'filename'    => $r->filename,
                'size_bytes'  => (int) $r->size_bytes,
                'user_name'   => $r->user_name,
                'created_at'  => $r->created_at,
            ])
            ->toArray();
    }

    public function findForDownload(int $restaurantId, string $uuid): ?array
    {
        $row = DB::table('report_exports')
            ->where('restaurant_id', $restaurantId)
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first(['storage_path', 'filename', 'format']);

        if ($row === null) {
            return null;
        }

        return [
            'storage_path' => $row->storage_path,
            'filename'     => $row->filename,
            'format'       => $row->format,
        ];
    }
}
