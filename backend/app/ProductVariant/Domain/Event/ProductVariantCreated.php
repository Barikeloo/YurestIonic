<?php

declare(strict_types=1);

namespace App\ProductVariant\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductVariantCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $variantId,
        private string $productId,
        private string $variantName,
        private int $priceCents,
        private int $stock,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'catalog.variant_created';
    }

    public function auditEntityType(): string
    {
        return 'product_variant';
    }

    public function auditEntityId(): string
    {
        return $this->variantId;
    }

    public function auditMetadata(): array
    {
        return [
            'variant_name'    => $this->variantName,
            'price_cents'     => $this->priceCents,
            'price_formatted' => number_format($this->priceCents / 100, 2).' €',
            'stock'           => $this->stock,
            'product_id'      => $this->productId,
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
