<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

/**
 * Snapshot of the archived audit-log corpus for one restaurant.
 *
 * Range and monthly breakdown are computed against created_at — what the
 * admin wants to know is *when in history* the archived events happened,
 * not when the archival cron ran.
 */
final readonly class ArchivedAuditStats
{
    /**
     * @param  list<MonthlyArchivedCount>  $monthlyBreakdown
     * @param  list<CategoryArchivedCount> $byCategory
     * @param  list<TopArchivedUser>       $topUsers
     * @param  list<AnomalyKindCount>      $byAnomalyKind
     */
    public function __construct(
        public int $total,
        public ?\DateTimeImmutable $oldestCreatedAt,
        public ?\DateTimeImmutable $newestCreatedAt,
        public array $monthlyBreakdown,
        public array $byCategory = [],
        public array $topUsers = [],
        public array $byAnomalyKind = [],
    ) {}

    public static function empty(): self
    {
        return new self(
            total: 0,
            oldestCreatedAt: null,
            newestCreatedAt: null,
            monthlyBreakdown: [],
            byCategory: [],
            topUsers: [],
            byAnomalyKind: [],
        );
    }
}
