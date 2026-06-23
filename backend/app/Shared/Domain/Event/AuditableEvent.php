<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

interface AuditableEvent extends DomainEvent
{
    public function auditSlug(): string;

    public function auditEntityType(): string;

    public function auditEntityId(): string;

    public function auditMetadata(): array;

    public function auditBefore(): ?array;

    public function auditAfter(): ?array;
}
