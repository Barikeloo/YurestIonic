<?php

declare(strict_types=1);

namespace App\Audit\Domain\Interfaces;

use App\Audit\Domain\AuditLogPage;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\ListAuditLogsCriteria;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;

interface AuditLogRepositoryInterface
{
    public function save(AuditLog $auditLog): void;

    public function findByUuid(Uuid $restaurantId, Uuid $uuid): ?AuditLog;

    public function list(ListAuditLogsCriteria $criteria): AuditLogPage;

    /**
     * Returns the latest integrity_hash for the restaurant chain. Used by the recorder
     * to build the chain. Must lock the chain head until commit (SELECT ... FOR UPDATE).
     * Returns null if the restaurant has no audit logs yet.
     */
    public function lockAndGetLastHashForRestaurant(Uuid $restaurantId): ?string;

    /**
     * Counts how many events with the given slug were recorded for the given user
     * within the last $withinSeconds. Used by the anomaly detector for burst rules.
     */
    public function countRecentByActionAndUser(
        Uuid $restaurantId,
        ActionSlug $slug,
        Uuid $userId,
        int $withinSeconds,
    ): int;

    /**
     * Returns every audit log for a restaurant ordered by id ASC (oldest first).
     * Used by the chain verifier to walk the entire chain sequentially.
     *
     * @return list<AuditLog>
     */
    public function findAllByRestaurantOrdered(Uuid $restaurantId): array;
}
