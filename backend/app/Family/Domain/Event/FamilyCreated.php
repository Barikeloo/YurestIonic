<?php

declare(strict_types=1);

namespace App\Family\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class FamilyCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $familyId,
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
        return 'family.created';
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
        return ['family_name' => $this->name];
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
