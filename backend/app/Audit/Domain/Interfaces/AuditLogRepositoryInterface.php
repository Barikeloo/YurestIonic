<?php

declare(strict_types=1);

namespace App\Audit\Domain\Interfaces;

use App\Audit\Domain\ArchiveStats;
use App\Audit\Domain\AuditLogPage;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\ListAuditLogsCriteria;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Audit\Domain\ValueObject\ArchivedAuditStats;
use App\Shared\Domain\ValueObject\Uuid;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $auditLog): void;

    public function findByUuid(Uuid $restaurantId, Uuid $uuid, bool $includeArchived = false): ?AuditLog;

    public function list(ListAuditLogsCriteria $criteria): AuditLogPage;

    public function lockAndGetLastHashForRestaurant(Uuid $restaurantId): ?string;

    public function countRecentByActionAndUser(
        Uuid $restaurantId,
        ActionSlug $slug,
        Uuid $userId,
        int $withinSeconds,
        bool $includeArchived = false,
    ): int;

    public function findAllByRestaurantOrdered(Uuid $restaurantId): array;

    public function bulkArchive(
        ?Uuid $restaurantId,
        \DateTimeImmutable $threshold,
        bool $dryRun,
    ): array;

    public function getArchivedStats(
        Uuid $restaurantId,
        ?\DateTimeImmutable $dateFrom = null,
        ?\DateTimeImmutable $dateTo = null,
    ): ArchivedAuditStats;

    public function streamForExport(ListAuditLogsCriteria $criteria): iterable;
}
