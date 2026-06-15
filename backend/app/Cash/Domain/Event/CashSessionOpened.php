<?php

declare(strict_types=1);

namespace App\Cash\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class CashSessionOpened implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $cashSessionId,
        private string $openingFloatFormatted,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'caja.opened';
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
        return ['opening_float_formatted' => $this->openingFloatFormatted];
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
