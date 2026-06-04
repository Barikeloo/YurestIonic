<?php

declare(strict_types=1);

namespace App\Audit\Application\GetArchivedAuditStats;

use App\Audit\Domain\ValueObject\ArchivedAuditStats;
use App\Audit\Domain\ValueObject\MonthlyArchivedCount;

final readonly class GetArchivedAuditStatsResponse
{
    private function __construct(
        public ArchivedAuditStats $stats,
    ) {}

    public static function create(ArchivedAuditStats $stats): self
    {
        return new self(stats: $stats);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'total' => $this->stats->total,
            'oldest_created_at' => $this->stats->oldestCreatedAt?->format(\DateTimeInterface::ATOM),
            'newest_created_at' => $this->stats->newestCreatedAt?->format(\DateTimeInterface::ATOM),
            'monthly_breakdown' => array_map(
                static fn (MonthlyArchivedCount $m): array => [
                    'month' => $m->month,
                    'count' => $m->count,
                ],
                $this->stats->monthlyBreakdown,
            ),
        ];
    }
}
