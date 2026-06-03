<?php

declare(strict_types=1);

namespace App\Audit\Domain;

use App\Shared\Domain\ValueObject\Uuid;

/**
 * Per-restaurant outcome of an archival run. Carries the data the operator
 * needs to confirm the job did what was expected: how many rows were
 * touched and the time range of those rows.
 */
final readonly class ArchiveStats
{
    public function __construct(
        public Uuid $restaurantId,
        public int $archivedCount,
        public ?\DateTimeImmutable $oldestCreatedAt,
        public ?\DateTimeImmutable $newestCreatedAt,
    ) {}
}
