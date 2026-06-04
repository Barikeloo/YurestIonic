<?php

declare(strict_types=1);

namespace App\Audit\Application\GetArchivedAuditStats;

final readonly class GetArchivedAuditStatsCommand
{
    public function __construct(
        public string $restaurantId,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
    ) {}
}
