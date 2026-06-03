<?php

declare(strict_types=1);

namespace App\Audit\Application\ArchiveAuditData;

use App\Audit\Domain\ArchiveStats;

final readonly class ArchiveOldAuditLogsResponse
{
    /**
     * @param  list<ArchiveStats>  $perRestaurant
     */
    private function __construct(
        public bool $dryRun,
        public \DateTimeImmutable $thresholdDate,
        public int $totalArchived,
        public array $perRestaurant,
    ) {}

    /**
     * @param  list<ArchiveStats>  $perRestaurant
     */
    public static function create(
        bool $dryRun,
        \DateTimeImmutable $thresholdDate,
        array $perRestaurant,
    ): self {
        $total = 0;
        foreach ($perRestaurant as $stat) {
            $total += $stat->archivedCount;
        }

        return new self(
            dryRun: $dryRun,
            thresholdDate: $thresholdDate,
            totalArchived: $total,
            perRestaurant: $perRestaurant,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dry_run' => $this->dryRun,
            'threshold_date' => $this->thresholdDate->format(\DateTimeInterface::ATOM),
            'total_archived' => $this->totalArchived,
            'per_restaurant' => array_map(static fn (ArchiveStats $stat): array => [
                'restaurant_uuid' => $stat->restaurantId->value(),
                'archived_count' => $stat->archivedCount,
                'oldest_created_at' => $stat->oldestCreatedAt?->format(\DateTimeInterface::ATOM),
                'newest_created_at' => $stat->newestCreatedAt?->format(\DateTimeInterface::ATOM),
            ], $this->perRestaurant),
        ];
    }
}
