<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class UserUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $userUuid,
        private array $before,
        private array $after,
        private array $metadata,
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
        return 'user.updated';
    }

    public function auditEntityType(): string
    {
        return 'user';
    }

    public function auditEntityId(): string
    {
        return $this->userUuid;
    }

    public function auditMetadata(): array
    {
        if ($this->restaurantUuid !== null) {
            return array_merge($this->metadata, ['restaurant_uuid' => $this->restaurantUuid]);
        }

        return $this->metadata;
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
