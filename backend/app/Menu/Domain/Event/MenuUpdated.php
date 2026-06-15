<?php

declare(strict_types=1);

namespace App\Menu\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class MenuUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $menuUuid,
        private string $menuName,
        private int $sectionsCount,
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
        return 'menu.updated';
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
            'sections_count' => $this->sectionsCount,
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
