<?php

declare(strict_types=1);

namespace App\Cash\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class CashSessionClosingCancelled implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $cashSessionId,
        private string $statusBefore,
        private string $statusAfter,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'caja.closing_cancelled';
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
        return [];
    }

    public function auditBefore(): ?array
    {
        return ['status' => $this->statusBefore];
    }

    public function auditAfter(): ?array
    {
        return ['status' => $this->statusAfter];
    }
}
