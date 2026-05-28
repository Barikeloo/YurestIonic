<?php

declare(strict_types=1);

namespace App\Audit\Application\ListAuditEvents;

final readonly class ListAuditEventsCommand
{
    public function __construct(
        public string $restaurantId,
        public ?string $category = null,
        public ?string $severity = null,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $search = null,
        public bool $anomalyOnly = false,
        public ?string $cursor = null,
        public ?string $sinceUuid = null,
    ) {}
}
