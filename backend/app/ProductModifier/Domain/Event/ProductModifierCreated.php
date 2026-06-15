<?php

declare(strict_types=1);

namespace App\ProductModifier\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductModifierCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $modifierId,
        private string $productId,
        private string $modifierName,
        private string $modifierType,
        private int $priceCents,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'catalog.modifier_created';
    }

    public function auditEntityType(): string
    {
        return 'product_modifier';
    }

    public function auditEntityId(): string
    {
        return $this->modifierId;
    }

    public function auditMetadata(): array
    {
        return [
            'modifier_name'    => $this->modifierName,
            'modifier_type'    => $this->modifierType,
            'price_cents'      => $this->priceCents,
            'price_formatted'  => number_format($this->priceCents / 100, 2).' €',
            'product_id'       => $this->productId,
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
