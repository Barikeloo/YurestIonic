<?php

declare(strict_types=1);

namespace App\Product\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductPriceChanged implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $productId,
        private string $productName,
        private int $oldPriceCents,
        private int $newPriceCents,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'product.price_changed';
    }

    public function auditEntityType(): string
    {
        return 'product';
    }

    public function auditEntityId(): string
    {
        return $this->productId;
    }

    public function auditMetadata(): array
    {
        return [
            'product_name'        => $this->productName,
            'price_before_cents'  => $this->oldPriceCents,
            'price_before_formatted' => number_format($this->oldPriceCents / 100, 2).' €',
            'price_after_cents'   => $this->newPriceCents,
            'price_after_formatted'  => number_format($this->newPriceCents / 100, 2).' €',
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
