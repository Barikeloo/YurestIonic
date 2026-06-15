<?php

declare(strict_types=1);

namespace App\ProductVariant\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductVariantUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $variantId,
        private array $before,
        private array $after,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'catalog.variant_updated';
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
        return ['variant_name' => $this->after['name']];
    }

    public function auditBefore(): ?array
    {
        return $this->before;
    }

    public function auditAfter(): ?array
    {
        return $this->after;
    }
}
