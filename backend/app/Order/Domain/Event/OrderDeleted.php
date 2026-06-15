<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class OrderDeleted implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $orderUuid,
        private array $before,
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
        return 'order.deleted';
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
        return [];
    }

    public function auditBefore(): ?array
    {
        return $this->before;
    }

    public function auditAfter(): ?array
    {
        return null;
    }
}
