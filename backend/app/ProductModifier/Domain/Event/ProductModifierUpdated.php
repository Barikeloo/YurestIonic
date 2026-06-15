<?php

declare(strict_types=1);

namespace App\ProductModifier\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductModifierUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    /**
     * @param array{name: string, type: string, is_required: bool, selection_type: string, price: int, active: bool, sort_order: int} $before
     * @param array{name: string, type: string, is_required: bool, selection_type: string, price: int, active: bool, sort_order: int} $after
     */
    public function __construct(
        private string $modifierId,
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
        return 'catalog.modifier_updated';
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
        return ['modifier_name' => $this->after['name']];
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
