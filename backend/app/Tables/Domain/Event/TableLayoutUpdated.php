<?php

declare(strict_types=1);

namespace App\Tables\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class TableLayoutUpdated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $tableId,
        private ?array $before,
        private array  $after,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'table.layout_updated';
    }

    public function auditEntityType(): string
    {
        return 'table';
    }

    public function auditEntityId(): string
    {
        return $this->tableId;
    }

    public function auditMetadata(): array
    {
        return ['shape' => $this->after['shape']];
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
