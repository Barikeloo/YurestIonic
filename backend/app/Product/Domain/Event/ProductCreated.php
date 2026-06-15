<?php

declare(strict_types=1);

namespace App\Product\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $productId,
        private string $productName,
        private int $priceCents,
        private string $familyId,
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
        return 'product.created';
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
            'product_name'    => $this->productName,
            'price_cents'     => $this->priceCents,
            'price_formatted' => number_format($this->priceCents / 100, 2).' €',
            'family_id'       => $this->familyId,
            'active'          => $this->active,
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
