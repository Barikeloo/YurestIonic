<?php

declare(strict_types=1);

namespace App\Product\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class ProductAvailabilityChanged implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $productId,
        private string $productName,
        private bool $available,
        private string $restaurantId,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function productId(): string
    {
        return $this->productId;
    }

    public function available(): bool
    {
        return $this->available;
    }

    public function restaurantId(): string
    {
        return $this->restaurantId;
    }

    public function auditSlug(): string
    {
        return 'product.availability_changed';
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
            'product_name' => $this->productName,
            'available'    => $this->available,
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
