<?php

declare(strict_types=1);

namespace App\User\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class LoginFailed implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $entityId,
        private string $email,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'auth.login_failed';
    }

    public function auditEntityType(): string
    {
        return 'auth_attempt';
    }

    public function auditEntityId(): string
    {
        return $this->entityId;
    }

    public function auditMetadata(): array
    {
        return ['email' => $this->email];
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
