<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ChargeSessionCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $chargeSessionId,
        private string $orderId,
        private int $dinersCount,
        private int $totalCents,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.charge_session_created';
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
            'order_id'     => $this->orderId,
            'diners_count' => $this->dinersCount,
            'total_cents'  => $this->totalCents,
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
