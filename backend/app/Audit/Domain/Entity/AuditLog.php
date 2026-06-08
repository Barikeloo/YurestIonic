<?php

declare(strict_types=1);

namespace App\Audit\Domain\Entity;

use App\Audit\Domain\ValueObject\ActionSlug;
use App\Audit\Domain\ValueObject\Category;
use App\Audit\Domain\ValueObject\Severity;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

final class AuditLog
{

    private function __construct(
        private readonly Uuid $uuid,
        private readonly Uuid $restaurantId,
        private readonly string $entityType,
        private readonly string $entityId,
        private readonly ActionSlug $action,
        private readonly Category $category,
        private readonly Severity $severity,
        private readonly string $summary,
        private readonly ?string $reason,
        private readonly ?Uuid $sessionId,
        private readonly ?string $anomalyKind,
        private readonly string $integrityHash,
        private readonly ?string $prevHash,
        private readonly array $metadata,
        private readonly ?Uuid $userId,
        private readonly ?array $before,
        private readonly ?array $after,
        private readonly ?string $ipAddress,
        private readonly ?string $deviceId,
        private readonly DomainDateTime $createdAt,
        private readonly ?\DateTimeImmutable $archivedAt = null,
    ) {}

    public static function dddCreate(
        Uuid $uuid,
        Uuid $restaurantId,
        string $entityType,
        string $entityId,
        ActionSlug $action,
        Category $category,
        Severity $severity,
        string $summary,
        string $integrityHash,
        ?string $prevHash,
        ?string $reason = null,
        ?Uuid $sessionId = null,
        ?string $anomalyKind = null,
        array $metadata = [],
        ?Uuid $userId = null,
        ?array $before = null,
        ?array $after = null,
        ?string $ipAddress = null,
        ?string $deviceId = null,
        ?DomainDateTime $createdAt = null,
    ): self {
        return new self(
            uuid: $uuid,
            restaurantId: $restaurantId,
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
            createdAt: $createdAt ?? DomainDateTime::now(),
        );
    }

    public static function fromPersistence(
        string $uuid,
        string $restaurantId,
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
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $archivedAt = null,
    ): self {
        return new self(
            uuid: Uuid::create($uuid),
            restaurantId: Uuid::create($restaurantId),
            entityType: $entityType,
            entityId: $entityId,
            action: ActionSlug::create($action),
            category: Category::create($category),
            severity: Severity::create($severity),
            summary: $summary,
            reason: $reason,
            sessionId: $sessionId !== null ? Uuid::create($sessionId) : null,
            anomalyKind: $anomalyKind,
            integrityHash: $integrityHash,
            prevHash: $prevHash,
            metadata: $metadata,
            userId: $userId !== null ? Uuid::create($userId) : null,
            before: $before,
            after: $after,
            ipAddress: $ipAddress,
            deviceId: $deviceId,
            createdAt: DomainDateTime::create($createdAt),
            archivedAt: $archivedAt,
        );
    }

    public function uuid(): Uuid
    {
        return $this->uuid;
    }

    public function restaurantId(): Uuid
    {
        return $this->restaurantId;
    }

    public function entityType(): string
    {
        return $this->entityType;
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function action(): ActionSlug
    {
        return $this->action;
    }

    public function category(): Category
    {
        return $this->category;
    }

    public function severity(): Severity
    {
        return $this->severity;
    }

    public function summary(): string
    {
        return $this->summary;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function sessionId(): ?Uuid
    {
        return $this->sessionId;
    }

    public function anomalyKind(): ?string
    {
        return $this->anomalyKind;
    }

    public function integrityHash(): string
    {
        return $this->integrityHash;
    }

    public function prevHash(): ?string
    {
        return $this->prevHash;
    }

    public function metadata(): array
    {
        return $this->metadata;
    }

    public function userId(): ?Uuid
    {
        return $this->userId;
    }

    public function before(): ?array
    {
        return $this->before;
    }

    public function after(): ?array
    {
        return $this->after;
    }

    public function ipAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function deviceId(): ?string
    {
        return $this->deviceId;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function archivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    public function hasAnomaly(): bool
    {
        return $this->anomalyKind !== null;
    }
}
