<?php

declare(strict_types=1);

namespace App\Audit\Domain;

use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;

final readonly class AuditEventDraft
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public ?Uuid $restaurantId = null,
        public ActionSlug $slug,
        public string $entityType,
        public string $entityId,
        public ?Uuid $userId = null,
        public ?string $ipAddress = null,
        public ?string $deviceId = null,
        public ?Uuid $sessionId = null,
        public ?string $reason = null,
        public ?array $before = null,
        public ?array $after = null,
        public array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toCatalogContext(): array
    {
        return [
            'entity_id' => $this->entityId,
            'entity_type' => $this->entityType,
            'device_id' => $this->deviceId ?? '—',
            'ip_address' => $this->ipAddress ?? '—',
            'session_id' => $this->sessionId?->value() ?? '—',
            'user_id' => $this->userId?->value() ?? '—',
            'reason' => $this->reason ?? '—',
            'before' => $this->before ?? [],
            'after' => $this->after ?? [],
            'metadata' => $this->metadata,
        ];
    }
}
