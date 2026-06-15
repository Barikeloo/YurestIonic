<?php

declare(strict_types=1);

namespace App\Sale\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class SaleLineAdded implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $saleLineId,
        private string $saleId,
        private string $orderLineId,
        private int $quantity,
        private int $priceCents,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'sale.line_added';
    }

    public function auditEntityType(): string
    {
        return 'sale_line';
    }

    public function auditEntityId(): string
    {
        return $this->saleLineId;
    }

    public function auditMetadata(): array
    {
        return [
            'sale_id'       => $this->saleId,
            'order_line_id' => $this->orderLineId,
            'quantity'      => $this->quantity,
            'price_cents'   => $this->priceCents,
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
