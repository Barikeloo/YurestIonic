<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class OrderComandaSent implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $orderUuid,
        private array $items,
        private int $totalLines,
        private string $itemsSummary,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'order.comanda_sent';
    }

    public function auditEntityType(): string
    {
        return 'order';
    }

    public function auditEntityId(): string
    {
        return $this->orderUuid;
    }

    public function auditMetadata(): array
    {
        return [
            'order_id' => $this->orderUuid,
            'items' => $this->items,
            'total_lines' => $this->totalLines,
            'items_summary' => $this->itemsSummary,
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
