<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class SaleCancelled implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $saleId,
        private string $orderId,
        private int $totalCents,
        private int $cashRefundedCents,
        private int $paymentsRemoved,
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
        return 'sale.cancelled';
    }

    public function auditEntityType(): string
    {
        return 'sale';
    }

    public function auditEntityId(): string
    {
        return $this->saleId;
    }

    public function auditMetadata(): array
    {
        return [
            'order_id'            => $this->orderId,
            'total_cents'         => $this->totalCents,
            'cash_refunded_cents' => $this->cashRefundedCents,
            'payments_removed'    => $this->paymentsRemoved,
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
