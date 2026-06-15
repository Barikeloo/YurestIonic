<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ChargeSessionLineRefunded implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $orderLineId,
        private string $chargeSessionId,
        private int $cashMovementCents,
        private string $reason,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.line_refunded';
    }

    public function auditEntityType(): string
    {
        return 'order_line';
    }

    public function auditEntityId(): string
    {
        return $this->orderLineId;
    }

    public function auditMetadata(): array
    {
        return [
            'charge_session_id'   => $this->chargeSessionId,
            'cash_movement_cents' => $this->cashMovementCents,
            'reason'              => $this->reason,
        ];
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
