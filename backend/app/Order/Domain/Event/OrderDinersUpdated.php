<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class OrderDinersUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $orderUuid,
        private int $beforeDiners,
        private int $afterDiners,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'order.diners_updated';
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
        return ['diners' => $this->beforeDiners];
    }

    public function auditAfter(): ?array
    {
        return ['diners' => $this->afterDiners];
    }
}
