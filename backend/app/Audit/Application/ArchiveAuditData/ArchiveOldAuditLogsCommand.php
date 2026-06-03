<?php

declare(strict_types=1);

namespace App\Audit\Application\ArchiveAuditData;

final readonly class ArchiveOldAuditLogsCommand
{
    public function __construct(
        public int $olderThanDays,
        public ?string $restaurantUuid,
        public bool $dryRun,
    ) {}
}
