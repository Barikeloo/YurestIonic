<?php

declare(strict_types=1);

namespace App\Audit\Domain;

use App\Shared\Domain\ValueObject\Uuid;

final readonly class ListAuditLogsCriteria
{
    public function __construct(
        public Uuid $restaurantId,
        public ?string $category = null,
        public ?string $severity = null,
        public ?Uuid $userId = null,
        public ?string $deviceId = null,
        public ?\DateTimeImmutable $dateFrom = null,
        public ?\DateTimeImmutable $dateTo = null,
        public ?string $search = null,
        public bool $anomalyOnly = false,
        public ?\DateTimeImmutable $cursorCreatedAt = null,
        public ?int $cursorInternalId = null,
        public ?Uuid $sinceUuid = null,
        public int $limit = 50,
    ) {}
}
