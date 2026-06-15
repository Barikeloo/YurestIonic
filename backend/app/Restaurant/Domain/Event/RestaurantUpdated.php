<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class RestaurantUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $restaurantUuid,
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
        return 'restaurant.updated';
    }

    public function auditEntityType(): string
    {
        return 'restaurant';
    }

    public function auditEntityId(): string
    {
        return $this->restaurantUuid;
    }

    public function auditMetadata(): array
    {
        return [
            'restaurant_name' => $this->after['name'] ?? '',
        ];
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
