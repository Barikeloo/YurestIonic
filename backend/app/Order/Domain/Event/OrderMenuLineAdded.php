<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class OrderMenuLineAdded implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $orderUuid,
        private string $menuId,
        private string $menuName,
        private int $quantity,
        private int $priceCents,
        private ?int $dinerNumber,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'order.menu_line_added';
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
            'menu_id' => $this->menuId,
            'menu_name' => $this->menuName,
            'quantity' => $this->quantity,
            'price_cents' => $this->priceCents,
            'diner_number' => $this->dinerNumber,
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
