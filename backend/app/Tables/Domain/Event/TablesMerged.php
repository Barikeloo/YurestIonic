<?php

declare(strict_types=1);

namespace App\Tables\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class TablesMerged implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $groupId,
        private array $tableNames,
        private string $restaurantId,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function restaurantId(): string
    {
        return $this->restaurantId;
    }

    public function auditSlug(): string
    {
        return 'table.merged';
    }

    public function auditEntityType(): string
    {
        return 'table_group';
    }

    public function auditEntityId(): string
    {
        return $this->groupId;
    }

    public function auditMetadata(): array
    {
        return ['tables_label' => implode(', ', $this->tableNames)];
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
