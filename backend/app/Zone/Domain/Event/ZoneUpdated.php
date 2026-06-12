<?php

declare(strict_types=1);

namespace App\Zone\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ZoneUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    /**
     * @param array{name: string} $before
     * @param array{name: string} $after
     */
    public function __construct(
        private string $zoneId,
        private array $before,
        private array $after,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'zone.updated';
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
        return ['zone_name' => $this->after['name']];
    }

    public function auditBefore(): ?array
    {
        return $this->before;
    }

    public function auditAfter(): ?array
    {
        return $this->after;
    }
}
