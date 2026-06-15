<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ChargeSessionDinersUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $chargeSessionId,
        private int $dinersBefore,
        private int $dinersAfter,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.diners_updated';
    }

    public function auditEntityType(): string
    {
        return 'charge_session';
    }

    public function auditEntityId(): string
    {
        return $this->chargeSessionId;
    }

    public function auditMetadata(): array
    {
        return [];
    }

    public function auditBefore(): ?array
    {
        return ['diners_count' => $this->dinersBefore];
    }

    public function auditAfter(): ?array
    {
        return ['diners_count' => $this->dinersAfter];
    }
}
