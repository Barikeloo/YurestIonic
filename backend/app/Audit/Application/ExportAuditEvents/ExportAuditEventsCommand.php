<?php

declare(strict_types=1);

namespace App\Audit\Application\ExportAuditEvents;

use App\Audit\Domain\ValueObject\ExportFormat;

final readonly class ExportAuditEventsCommand
{
    public function __construct(
        public string $restaurantId,
        public ExportFormat $format,
        public ?string $category = null,
        public ?string $severity = null,
        public ?string $userId = null,
        public ?string $deviceId = null,
        public ?string $dateFrom = null,
        public ?string $dateTo = null,
        public ?string $search = null,
        public bool $anomalyOnly = false,
        public bool $includeArchived = false,
    ) {}
}
