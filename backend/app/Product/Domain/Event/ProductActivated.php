<?php

declare(strict_types=1);

namespace App\Product\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductActivated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $productId,
        private string $productName,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'product.activated';
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
        return ['product_name' => $this->productName];
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
