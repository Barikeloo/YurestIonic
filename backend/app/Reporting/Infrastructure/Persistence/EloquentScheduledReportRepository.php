<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Persistence;

use App\Reporting\Domain\Interfaces\ScheduledReportRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

final class EloquentScheduledReportRepository implements ScheduledReportRepositoryInterface
{
    public function save(array $report): string
    {
        $uuid = (string) Uuid::uuid4();
        $now = now();

        DB::table('scheduled_reports')->insert([
            'uuid'                 => $uuid,
            'restaurant_id'        => $report['restaurant_id'],
            'report_type'          => $report['report_type'],
            'format'               => $report['format'],
            'frequency'            => $report['frequency'],
            'time'                 => $report['time'],
            'weekday'              => $report['weekday'] ?? null,
            'day_of_month'         => $report['day_of_month'] ?? null,
            'recipients'           => json_encode($report['recipients']),
            'name'                 => $report['name'],
            'active'               => $report['active'] ?? true,
            'next_run_at'          => $report['next_run_at'],
            'created_by_user_uuid' => $report['created_by_user_uuid'] ?? null,
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);

        return $uuid;
    }

    public function update(string $uuid, array $data): void
    {
        $update = [
            'updated_at' => now(),
        ];

        foreach (['report_type', 'format', 'frequency', 'time', 'weekday', 'day_of_month', 'name', 'active', 'next_run_at'] as $field) {
            if (array_key_exists($field, $data)) {
                $update[$field] = $data[$field];
            }
        }

        if (array_key_exists('recipients', $data)) {
            $update['recipients'] = json_encode($data['recipients']);
        }

        DB::table('scheduled_reports')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->update($update);
    }

    public function findByUuid(int $restaurantId, string $uuid): ?array
    {
        $row = DB::table('scheduled_reports')
            ->where('restaurant_id', $restaurantId)
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->rowToArray($row);
    }

    public function listForRestaurant(int $restaurantId): array
    {
        return DB::table('scheduled_reports')
            ->where('restaurant_id', $restaurantId)
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->map(fn ($r) => $this->rowToArray($r))
            ->toArray();
    }

    public function delete(string $uuid): void
    {
        DB::table('scheduled_reports')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function setActive(string $uuid, bool $active): void
    {
        DB::table('scheduled_reports')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->update([
                'active'     => $active,
                'updated_at' => now(),
            ]);
    }

    public function listDue(\DateTimeImmutable $now): array
    {
        return DB::table('scheduled_reports')
            ->where('active', true)
            ->where('next_run_at', '<=', $now->format('Y-m-d H:i:s'))
            ->whereNull('deleted_at')
            ->orderBy('next_run_at', 'ASC')
            ->get()
            ->map(fn ($r) => $this->rowToArray($r))
            ->toArray();
    }

    public function markRun(string $uuid, \DateTimeImmutable $lastRun, \DateTimeImmutable $nextRun): void
    {
        DB::table('scheduled_reports')
            ->where('uuid', $uuid)
            ->whereNull('deleted_at')
            ->update([
                'last_run_at' => $lastRun->format('Y-m-d H:i:s'),
                'next_run_at' => $nextRun->format('Y-m-d H:i:s'),
                'updated_at'  => now(),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function rowToArray(object $row): array
    {
        return [
            'id'                   => (int) $row->id,
            'uuid'                 => $row->uuid,
            'restaurant_id'        => (int) $row->restaurant_id,
            'report_type'          => $row->report_type,
            'format'               => $row->format,
            'frequency'            => $row->frequency,
            'time'                 => $row->time,
            'weekday'              => $row->weekday !== null ? (int) $row->weekday : null,
            'day_of_month'         => $row->day_of_month !== null ? (int) $row->day_of_month : null,
            'recipients'           => json_decode($row->recipients, true),
            'name'                 => $row->name,
            'active'               => (bool) $row->active,
            'last_run_at'          => $row->last_run_at,
            'next_run_at'          => $row->next_run_at,
            'created_by_user_uuid' => $row->created_by_user_uuid,
            'created_at'           => $row->created_at,
            'updated_at'           => $row->updated_at,
        ];
    }
}
