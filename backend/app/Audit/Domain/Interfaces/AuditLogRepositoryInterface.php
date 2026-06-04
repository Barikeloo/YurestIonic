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

    /**
     * Reads a single audit log. By default archived rows are hidden — set
     * $includeArchived to true for admin lookups that need the historical
     * record (e.g. when reviewing the chain verification details).
     */
    public function findByUuid(Uuid $restaurantId, Uuid $uuid, bool $includeArchived = false): ?AuditLog;

    /**
     * Lists audit logs according to the criteria. The criteria carries its own
     * `includeArchived` flag; archived rows are excluded by default.
     */
    public function list(ListAuditLogsCriteria $criteria): AuditLogPage;

    /**
     * Returns the latest integrity_hash for the restaurant chain. Used by the recorder
     * to build the chain. Must lock the chain head until commit (SELECT ... FOR UPDATE).
     * Returns null if the restaurant has no audit logs yet.
     *
     * Always reads across active + archived rows: the chain head is the last row
     * appended, regardless of whether it has been archived since.
     */
    public function lockAndGetLastHashForRestaurant(Uuid $restaurantId): ?string;

    /**
     * Counts how many events with the given slug were recorded for the given user
     * within the last $withinSeconds. Used by the anomaly detector for burst rules.
     *
     * Archived rows are excluded by default because anomaly windows are minutes,
     * not months. The flag exists for completeness should we ever recount over a
     * historical window.
     */
    public function countRecentByActionAndUser(
        Uuid $restaurantId,
        ActionSlug $slug,
        Uuid $userId,
        int $withinSeconds,
        bool $includeArchived = false,
    ): int;

    /**
     * Returns every audit log for a restaurant ordered by id ASC (oldest first).
     * Used by the chain verifier to walk the entire chain sequentially.
     *
     * Always reads across active + archived rows. Archiving must not break chain
     * verification, so this method does not accept an includeArchived flag.
     *
     * @return list<AuditLog>
     */
    public function findAllByRestaurantOrdered(Uuid $restaurantId): array;

    /**
     * Marks every unarchived log older than $threshold as archived. Returns
     * stats per restaurant for the operator.
     *
     *  - When $restaurantId is null, the operation runs across every
     *    restaurant; the returned list has one entry per restaurant that
     *    had matching rows.
     *  - When $restaurantId is set, the list contains at most one entry
     *    (or zero if nothing matched for that restaurant).
     *  - When $dryRun is true the rows are NOT modified; the same stats
     *    are computed so the operator can preview the impact.
     *
     * @return list<ArchiveStats>
     */
    public function bulkArchive(
        ?Uuid $restaurantId,
        \DateTimeImmutable $threshold,
        bool $dryRun,
    ): array;

    /**
     * Returns the snapshot used by the admin history panel: total archived
     * rows for the restaurant, the created_at range they span, and the
     * count grouped by `YYYY-MM` of their original created_at.
     *
     * The monthly breakdown is ordered chronologically. Empty when the
     * restaurant has no archived rows.
     */
    public function getArchivedStats(Uuid $restaurantId): ArchivedAuditStats;
}
