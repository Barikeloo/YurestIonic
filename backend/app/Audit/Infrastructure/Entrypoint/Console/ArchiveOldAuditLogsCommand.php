<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Entrypoint\Console;

use App\Audit\Application\ArchiveAuditData\ArchiveOldAuditLogs;
use App\Audit\Application\ArchiveAuditData\ArchiveOldAuditLogsCommand as UseCaseCommand;
use App\Audit\Domain\Exception\InvalidArchiveThresholdException;
use Illuminate\Console\Command;

final class ArchiveOldAuditLogsCommand extends Command
{
    protected $signature = 'audit:archive-old
        {--older-than-days=90 : Mark every audit log older than this many days as archived (default 90).}
        {--restaurant-uuid= : Restrict the archival to a single restaurant; omit to run across all restaurants.}
        {--dry-run : Compute the impact without modifying any row.}';

    protected $description = 'Marks audit logs older than the configured threshold as archived (soft archive, never deletes).';

    public function handle(ArchiveOldAuditLogs $useCase): int
    {
        $olderThanDays = (int) $this->option('older-than-days');
        $restaurantUuidOpt = $this->option('restaurant-uuid');
        $restaurantUuid = is_string($restaurantUuidOpt) && $restaurantUuidOpt !== '' ? $restaurantUuidOpt : null;
        $dryRun = (bool) $this->option('dry-run');

        try {
            $response = ($useCase)(new UseCaseCommand(
                olderThanDays: $olderThanDays,
                restaurantUuid: $restaurantUuid,
                dryRun: $dryRun,
            ));
        } catch (InvalidArchiveThresholdException $e) {
            $this->error($e->getMessage());
            return self::INVALID;
        } catch (\Throwable $e) {
            report($e);
            $this->error('Unexpected error: '.$e->getMessage());
            return self::FAILURE;
        }

        $this->info(sprintf(
            '%s archived %d audit log(s) older than %s.',
            $response->dryRun ? '[DRY-RUN]' : 'OK:',
            $response->totalArchived,
            $response->thresholdDate->format('Y-m-d H:i:s'),
        ));

        if ($response->perRestaurant !== []) {
            $this->table(
                ['Restaurant UUID', 'Archived', 'Oldest', 'Newest'],
                array_map(static fn ($stat): array => [
                    $stat->restaurantId->value(),
                    $stat->archivedCount,
                    $stat->oldestCreatedAt?->format('Y-m-d H:i:s') ?? '—',
                    $stat->newestCreatedAt?->format('Y-m-d H:i:s') ?? '—',
                ], $response->perRestaurant),
            );
        }

        return self::SUCCESS;
    }
}
