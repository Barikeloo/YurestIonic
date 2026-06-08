<?php

declare(strict_types=1);

namespace App\Audit\Application\ArchiveAuditData;

use App\Audit\Application\GetArchivedAuditStats\GetArchivedAuditStats;
use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Exception\InvalidArchiveThresholdException;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class ArchiveOldAuditLogs
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
        private readonly AuditRecorderInterface $auditRecorder,
        private readonly CacheRepository $cache,
    ) {}

    public function __invoke(ArchiveOldAuditLogsCommand $command): ArchiveOldAuditLogsResponse
    {
        if ($command->olderThanDays < 1) {
            throw InvalidArchiveThresholdException::nonPositiveDays($command->olderThanDays);
        }

        $now = new \DateTimeImmutable;
        $threshold = $now->modify("-{$command->olderThanDays} days");

        if ($threshold > $now) {
            throw InvalidArchiveThresholdException::thresholdInFuture($threshold);
        }

        $restaurantId = $command->restaurantUuid !== null
            ? Uuid::create($command->restaurantUuid)
            : null;

        $stats = $this->repository->bulkArchive($restaurantId, $threshold, $command->dryRun);

        if (! $command->dryRun) {
            foreach ($stats as $stat) {
                if ($stat->archivedCount === 0) {
                    continue;
                }

                $this->cache->forget(GetArchivedAuditStats::cacheKey($stat->restaurantId));

                $this->auditRecorder->record(new AuditEventDraft(
                    restaurantId: $stat->restaurantId,
                    slug: ActionSlug::create('audit.archived'),
                    entityType: 'audit_log',
                    entityId: $stat->restaurantId->value(),
                    metadata: [
                        'archived_count' => $stat->archivedCount,
                        'threshold_date_formatted' => $threshold->format('Y-m-d'),
                        'oldest' => $stat->oldestCreatedAt?->format(\DateTimeInterface::ATOM),
                        'newest' => $stat->newestCreatedAt?->format(\DateTimeInterface::ATOM),
                    ],
                ));
            }
        }

        return ArchiveOldAuditLogsResponse::create(
            dryRun: $command->dryRun,
            thresholdDate: $threshold,
            perRestaurant: $stats,
        );
    }
}
