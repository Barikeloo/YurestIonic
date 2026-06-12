<?php

declare(strict_types=1);

namespace App\Family\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class FamilyUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    /**
     * @param array{name: string} $before
     * @param array{name: string} $after
     */
    public function __construct(
        private string $familyId,
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
        return 'family.updated';
    }

    public function auditEntityType(): string
    {
        return 'family';
    }

    public function auditEntityId(): string
    {
        return $this->familyId;
    }

    public function auditMetadata(): array
    {
        return ['family_name' => $this->after['name']];
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
