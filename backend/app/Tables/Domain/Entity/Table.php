<?php

namespace App\Tables\Domain\Entity;

use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Event\TableCreated;
use App\Tables\Domain\Event\TableDeleted;
use App\Tables\Domain\Event\TableLayoutUpdated;
use App\Tables\Domain\Event\TableUpdated;
use App\Tables\Domain\ValueObject\TableLayout;
use App\Tables\Domain\ValueObject\TableName;

class Table
{
    use RecordsEvents;

    private function __construct(
        private Uuid $id,
        private Uuid $zoneId,
        private TableName $name,
        private ?Uuid $mergedTableGroupId,
        private ?TableLayout $layout,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(Uuid $zoneId, TableName $name): self
    {
        $now = DomainDateTime::now();

        $table = new self(
            id: Uuid::generate(),
            zoneId: $zoneId,
            name: $name,
            mergedTableGroupId: null,
            layout: null,
            createdAt: $now,
            updatedAt: $now,
        );

        $table->recordEvent(new TableCreated(
            tableId: $table->id->value(),
            name: $table->name->value(),
            zoneId: $table->zoneId->value(),
        ));

        return $table;
    }

    public static function fromPersistence(
        string $id,
        string $zoneId,
        string $name,
        ?string $mergedTableGroupId,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?int $posX = null,
        ?int $posY = null,
        ?int $width = null,
        ?int $height = null,
        string $shape = 'rect',
    ): self {
        $layout = ($posX !== null && $posY !== null && $width !== null && $height !== null)
            ? TableLayout::create($posX, $posY, $width, $height, $shape)
            : null;

        return new self(
            id: Uuid::create($id),
            zoneId: Uuid::create($zoneId),
            name: TableName::create($name),
            mergedTableGroupId: $mergedTableGroupId !== null ? Uuid::create($mergedTableGroupId) : null,
            layout: $layout,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function update(Uuid $zoneId, TableName $name): void
    {
        $before = ['zone_id' => $this->zoneId->value(), 'name' => $this->name->value()];

        $this->zoneId = $zoneId;
        $this->name = $name;
        $this->touch();

        $this->recordEvent(new TableUpdated(
            tableId: $this->id->value(),
            before: $before,
            after: ['zone_id' => $this->zoneId->value(), 'name' => $this->name->value()],
        ));
    }

    public function updateLayout(TableLayout $layout): void
    {
        $before = $this->layout?->toArray();

        $this->layout = $layout;
        $this->touch();

        $this->recordEvent(new TableLayoutUpdated(
            tableId: $this->id->value(),
            before: $before,
            after: $layout->toArray(),
        ));
    }

    public function delete(): void
    {
        $this->recordEvent(new TableDeleted(
            tableId: $this->id->value(),
            name: $this->name->value(),
            zoneId: $this->zoneId->value(),
        ));
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function zoneId(): Uuid
    {
        return $this->zoneId;
    }

    public function name(): TableName
    {
        return $this->name;
    }

    public function layout(): ?TableLayout
    {
        return $this->layout;
    }

    public function mergedTableGroupId(): ?Uuid
    {
        return $this->mergedTableGroupId;
    }

    public function isMerged(): bool
    {
        return $this->mergedTableGroupId !== null;
    }

    public function mergeWith(Uuid $groupId): void
    {
        $this->mergedTableGroupId = $groupId;
        $this->touch();
    }

    public function unmerge(): void
    {
        $this->mergedTableGroupId = null;
        $this->touch();
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
