<?php

declare(strict_types=1);

namespace App\Audit\Domain\ValueObject;

final readonly class ArchivedAuditStats
{

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
