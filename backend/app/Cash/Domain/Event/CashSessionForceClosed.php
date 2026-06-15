<?php

declare(strict_types=1);

namespace App\Cash\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class CashSessionForceClosed implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $cashSessionId,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'caja.force_closed';
    }

    public function auditEntityType(): string
    {
        return 'cash_session';
    }

    public function auditEntityId(): string
    {
        return $this->cashSessionId;
    }

    public function auditMetadata(): array
    {
        return ['delta_final_formatted' => '0.00 €'];
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
