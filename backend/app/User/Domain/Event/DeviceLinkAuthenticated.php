<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class DeviceLinkAuthenticated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $userUuid,
        private ?string $restaurantUuid = null,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'auth.device_link';
    }

    public function auditEntityType(): string
    {
        return 'user_session';
    }

    public function auditEntityId(): string
    {
        return $this->userUuid;
    }

    public function auditMetadata(): array
    {
        if ($this->restaurantUuid !== null) {
            return ['restaurant_uuid' => $this->restaurantUuid];
        }

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
