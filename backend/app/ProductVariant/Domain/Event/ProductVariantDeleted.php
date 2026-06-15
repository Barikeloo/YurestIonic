<?php

declare(strict_types=1);

namespace App\ProductVariant\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductVariantDeleted implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $variantId,
        private string $productId,
        private string $variantName,
        private int $priceCents,
        private int $stock,
        private bool $active,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'catalog.variant_deleted';
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
        return ['product_id' => $this->productId];
    }

    public function auditBefore(): ?array
    {
        return [
            'name'   => $this->variantName,
            'price'  => $this->priceCents,
            'stock'  => $this->stock,
            'active' => $this->active,
        ];
    }

    public function auditAfter(): ?array
    {
        return null;
    }
}
