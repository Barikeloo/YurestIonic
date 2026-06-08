<?php

declare(strict_types=1);

namespace App\Audit\Domain;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class ArchiveStats
{
    public function __construct(
        public Uuid $restaurantId,
        public int $archivedCount,
        public ?\DateTimeImmutable $oldestCreatedAt,
        public ?\DateTimeImmutable $newestCreatedAt,
    ) {}
}
