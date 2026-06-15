<?php

declare(strict_types=1);

namespace App\ProductModifier\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductModifierDeleted implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $modifierId,
        private string $productId,
        private string $modifierName,
        private string $modifierType,
        private int $priceCents,
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
        return 'catalog.modifier_deleted';
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
        return ['product_id' => $this->productId];
    }

    public function auditBefore(): ?array
    {
        return [
            'name'   => $this->modifierName,
            'type'   => $this->modifierType,
            'price'  => $this->priceCents,
            'active' => $this->active,
        ];
    }

    public function auditAfter(): ?array
    {
        return null;
    }
}
