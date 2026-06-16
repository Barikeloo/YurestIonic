<?php

declare(strict_types=1);

namespace App\Tables\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

/**
 * Group-level event: a set of tables was merged into a single group. Published
 * directly by the use case (it spans several aggregates, not one).
 */
final readonly class TablesMerged implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    /** @param list<string> $tableNames */
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
