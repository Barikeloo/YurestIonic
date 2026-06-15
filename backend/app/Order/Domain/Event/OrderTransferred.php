<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class OrderTransferred implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $orderUuid,
        private string $fromTableId,
        private string $toTableId,
        private string $restaurantId,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function restaurantId(): string
    {
        return $this->restaurantId;
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'order.transferred';
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
            'from_table_id' => $this->fromTableId,
            'to_table_id' => $this->toTableId,
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
