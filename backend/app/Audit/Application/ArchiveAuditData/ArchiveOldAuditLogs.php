<?php

declare(strict_types=1);

namespace App\Audit\Application\ArchiveAuditData;

use App\Audit\Domain\Exception\InvalidArchiveThresholdException;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Marks every audit log older than the configured threshold as archived.
 *
 * Retention semantics live in PLAN_AUDIT_RETENTION.md: archived rows stay
 * in the same table (soft archive), the integrity chain is untouched, and
 * archived data is hidden from the default list endpoint but still
 * accessible to admins via include_archived=1 and to the chain verifier.
 *
 * This use case is invoked from the Laravel scheduler weekly, and from the
 * `audit:archive-old` console command for ad-hoc runs.
 */
class ArchiveOldAuditLogs
{
    public function __construct(
        private readonly AuditLogRepositoryInterface $repository,
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

        return ArchiveOldAuditLogsResponse::create(
            dryRun: $command->dryRun,
            thresholdDate: $threshold,
            perRestaurant: $stats,
        );
    }
}
