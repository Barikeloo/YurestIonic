<?php

declare(strict_types=1);

namespace App\Zone\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ZoneDeleted implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $zoneId,
        private string $name,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'zone.deleted';
    }

    public function auditEntityType(): string
    {
        return 'zone';
    }

    public function auditEntityId(): string
    {
        return $this->zoneId;
    }

    public function auditMetadata(): array
    {
        return ['zone_name' => $this->name];
    }

    public function auditBefore(): ?array
    {
        return null;
    }

    public function auditAfter(): ?array
    {
        return null;
    }
}
