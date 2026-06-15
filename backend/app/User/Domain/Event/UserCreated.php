<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class UserCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $userUuid,
        private string $name,
        private string $email,
        private string $role,
        private bool $hasPin,
        private ?string $actorSuperAdminUuid = null,
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
        return 'user.created';
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
        $metadata = [
            'user_name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'has_pin' => $this->hasPin,
            'actor_type' => $this->actorSuperAdminUuid !== null ? 'super_admin' : 'restaurant_admin',
            'actor_super_admin_id' => $this->actorSuperAdminUuid,
        ];

        if ($this->restaurantUuid !== null) {
            $metadata['restaurant_uuid'] = $this->restaurantUuid;
        }

        return $metadata;
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
