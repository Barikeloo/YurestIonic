<?php

declare(strict_types=1);

namespace App\Product\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    /**
     * @param array{name: string, price_cents: int, family_id: string, tax_id: string, active: bool, allergens: array, image_src: ?string} $before
     * @param array{name: string, price_cents: int, family_id: string, tax_id: string, active: bool, allergens: array, image_src: ?string} $after
     */
    public function __construct(
        private string $productId,
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
        return 'product.updated';
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
        return ['product_name' => $this->after['name']];
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
