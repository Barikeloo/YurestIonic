<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class RestaurantCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $restaurantUuid,
        private string $restaurantName,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'restaurant.created';
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
            'restaurant_name' => $this->restaurantName,
            'restaurant_uuid' => $this->restaurantUuid,
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
