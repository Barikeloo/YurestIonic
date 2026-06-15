<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ChargeSessionCancelled implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $chargeSessionId,
        private ?string $paidFormatted,
        private int $paidDinersCount,
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
        return 'sale.charge_session_cancelled';
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
        return [
            'paid_formatted'   => $this->paidFormatted,
            'paid_diners_count' => $this->paidDinersCount,
            'reason'           => $this->reason,
        ];
    }

    public function auditBefore(): ?array
    {
        return ['status' => 'active'];
    }

    public function auditAfter(): ?array
    {
        return ['status' => 'cancelled'];
    }
}
