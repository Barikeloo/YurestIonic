<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * A domain event that should leave an audit trail. Carries only domain data;
 * request context (who/where) is supplied separately by the audit subscriber.
 */
interface AuditableEvent extends DomainEvent
{
    public function auditSlug(): string;

    public function auditEntityType(): string;

    public function auditEntityId(): string;

    /** @return array<string, mixed> */
    public function auditMetadata(): array;

    /** @return array<string, mixed>|null */
    public function auditBefore(): ?array;

    /** @return array<string, mixed>|null */
    public function auditAfter(): ?array;
}
