<?php

declare(strict_types=1);

namespace App\Menu\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class MenuDeactivated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $menuUuid,
        private string $menuName,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'menu.deactivated';
    }

    public function auditEntityType(): string
    {
        return 'menu';
    }

    public function auditEntityId(): string
    {
        return $this->menuUuid;
    }

    public function auditMetadata(): array
    {
        return [
            'menu_name' => $this->menuName,
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
