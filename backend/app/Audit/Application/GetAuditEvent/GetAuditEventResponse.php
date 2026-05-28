<?php

declare(strict_types=1);

namespace App\Audit\Application\GetAuditEvent;

use App\Audit\Domain\Entity\AuditLog;

final readonly class GetAuditEventResponse
{
    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function __construct(
        public string $uuid,
        public string $entityType,
        public string $entityId,
        public string $action,
        public string $category,
        public string $severity,
        public string $summary,
        public ?string $reason,
        public ?string $sessionId,
        public ?string $anomalyKind,
        public string $integrityHash,
        public ?string $prevHash,
        public array $metadata,
        public ?string $userId,
        public ?array $before,
        public ?array $after,
        public ?string $ipAddress,
        public ?string $deviceId,
        public string $createdAt,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public static function create(
        string $uuid,
        string $entityType,
        string $entityId,
        string $action,
        string $category,
        string $severity,
        string $summary,
        ?string $reason,
        ?string $sessionId,
        ?string $anomalyKind,
        string $integrityHash,
        ?string $prevHash,
        array $metadata,
        ?string $userId,
        ?array $before,
        ?array $after,
        ?string $ipAddress,
        ?string $deviceId,
        string $createdAt,
    ): self {
        return new self(
            uuid: $uuid,
            entityType: $entityType,
            entityId: $entityId,
            action: $action,
            category: $category,
            severity: $severity,
            summary: $summary,
            reason: $reason,
            sessionId: $sessionId,
            anomalyKind: $anomalyKind,
            integrityHash: $integrityHash,
            prevHash: $prevHash,
            metadata: $metadata,
            userId: $userId,
            before: $before,
            after: $after,
            ipAddress: $ipAddress,
            deviceId: $deviceId,
            createdAt: $createdAt,
        );
    }

    public static function fromAuditLog(AuditLog $log): self
    {
        return self::create(
            uuid: $log->uuid()->value(),
            entityType: $log->entityType(),
            entityId: $log->entityId(),
            action: $log->action()->value(),
            category: $log->category()->value(),
            severity: $log->severity()->value(),
            summary: $log->summary(),
            reason: $log->reason(),
            sessionId: $log->sessionId()?->value(),
            anomalyKind: $log->anomalyKind(),
            integrityHash: $log->integrityHash(),
            prevHash: $log->prevHash(),
            metadata: $log->metadata(),
            userId: $log->userId()?->value(),
            before: $log->before(),
            after: $log->after(),
            ipAddress: $log->ipAddress(),
            deviceId: $log->deviceId(),
            createdAt: $log->createdAt()->format('Y-m-d H:i:s'),
        );
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'entity_type' => $this->entityType,
            'entity_id' => $this->entityId,
            'action' => $this->action,
            'category' => $this->category,
            'severity' => $this->severity,
            'summary' => $this->summary,
            'reason' => $this->reason,
            'session_id' => $this->sessionId,
            'anomaly_kind' => $this->anomalyKind,
            'integrity_hash' => $this->integrityHash,
            'prev_hash' => $this->prevHash,
            'metadata' => $this->metadata,
            'user_id' => $this->userId,
            'before' => $this->before,
            'after' => $this->after,
            'ip_address' => $this->ipAddress,
            'device_id' => $this->deviceId,
            'created_at' => $this->createdAt,
        ];
    }
}
