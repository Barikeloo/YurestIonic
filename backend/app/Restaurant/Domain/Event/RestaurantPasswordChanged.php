<?php

declare(strict_types=1);

namespace App\Restaurant\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class RestaurantPasswordChanged implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $restaurantUuid,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'auth.password_changed';
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
        return [];
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
