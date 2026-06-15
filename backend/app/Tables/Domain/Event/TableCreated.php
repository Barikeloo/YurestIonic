<?php

declare(strict_types=1);

namespace App\Tables\Domain\Event;

use App\Shared\Domain\Event\AuditableEvent;

final readonly class TableCreated implements AuditableEvent
{
    private \DateTimeImmutable $occurredOn;

    public function __construct(
        private string $tableId,
        private string $name,
        private string $zoneId,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }

    public function occurredOn(): \DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function auditSlug(): string
    {
        return 'table.created';
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
        return [
            'table_name' => $this->name,
            'zone_id' => $this->zoneId,
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
