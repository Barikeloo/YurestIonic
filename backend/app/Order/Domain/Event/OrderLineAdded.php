<?php

declare(strict_types=1);

namespace App\Order\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class OrderLineAdded implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $orderUuid,
        private string $productId,
        private string $productName,
        private ?string $variantName,
        private int $quantity,
        private int $unitPriceCents,
        private bool $merged,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'order.line_added';
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
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'variant_name' => $this->variantName,
            'quantity' => $this->quantity,
            'unit_price_cents' => $this->unitPriceCents,
            'merged' => $this->merged,
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
