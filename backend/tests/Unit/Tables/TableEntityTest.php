<?php

namespace Tests\Unit\Tables;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Event\TableCreated;
use App\Tables\Domain\Event\TableDeleted;
use App\Tables\Domain\Event\TableLayoutUpdated;
use App\Tables\Domain\Event\TableUpdated;
use App\Tables\Domain\ValueObject\TableLayout;
use App\Tables\Domain\ValueObject\TableName;
use PHPUnit\Framework\TestCase;

class TableEntityTest extends TestCase
{
    private const TABLE_ID = '00000000-0000-4000-8000-000000000000';
    private const ZONE_ID = '00000000-0000-4000-8000-0000000000aa';
    private const ZONE_ID_2 = '00000000-0000-4000-8000-0000000000bb';

    private function existing(): Table
    {
        return Table::fromPersistence(self::TABLE_ID, self::ZONE_ID, 'Mesa 1', null, new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function test_ddd_create_records_a_table_created_event(): void
    {
        $table = Table::dddCreate(Uuid::create(self::ZONE_ID), TableName::create('Mesa 1'));

        $events = $table->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(TableCreated::class, $events[0]);
        $this->assertSame(['table_name' => 'Mesa 1', 'zone_id' => self::ZONE_ID], $events[0]->auditMetadata());
        $this->assertSame([], $table->pullDomainEvents());
    }

    public function test_update_records_a_table_updated_event_with_before_after(): void
    {
        $table = $this->existing();

        $table->update(Uuid::create(self::ZONE_ID_2), TableName::create('Mesa 2'));

        $events = $table->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TableUpdated::class, $events[0]);
        $this->assertSame(['zone_id' => self::ZONE_ID, 'name' => 'Mesa 1'], $events[0]->auditBefore());
        $this->assertSame(['zone_id' => self::ZONE_ID_2, 'name' => 'Mesa 2'], $events[0]->auditAfter());
    }

    public function test_delete_records_a_table_deleted_event(): void
    {
        $table = $this->existing();

        $table->delete();

        $events = $table->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TableDeleted::class, $events[0]);
    }

    public function test_merge_and_unmerge_record_no_entity_events(): void
    {
        $table = $this->existing();

        $table->mergeWith(Uuid::generate());
        $table->unmerge();

        // Group operations are published by the use case, not the aggregate.
        $this->assertSame([], $table->pullDomainEvents());
    }

    public function test_from_persistence_without_layout_returns_null_layout(): void
    {
        $table = $this->existing();

        $this->assertNull($table->layout());
    }

    public function test_from_persistence_with_layout_hydrates_value_object(): void
    {
        $table = Table::fromPersistence(
            id: self::TABLE_ID,
            zoneId: self::ZONE_ID,
            name: 'Mesa 1',
            mergedTableGroupId: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            posX: 100,
            posY: 50,
            width: 120,
            height: 70,
            shape: 'rect',
        );

        $layout = $table->layout();
        $this->assertNotNull($layout);
        $this->assertSame(100,    $layout->posX);
        $this->assertSame(50,     $layout->posY);
        $this->assertSame(120,    $layout->width);
        $this->assertSame(70,     $layout->height);
        $this->assertSame('rect', $layout->shape);
    }

    public function test_update_layout_records_table_layout_updated_event(): void
    {
        $table  = $this->existing();
        $layout = TableLayout::create(200, 100, 100, 60, 'circle');

        $table->updateLayout($layout);

        $events = $table->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(TableLayoutUpdated::class, $events[0]);
    }

    public function test_update_layout_before_is_null_when_table_had_no_layout(): void
    {
        $table  = $this->existing();
        $layout = TableLayout::create(0, 0, 80, 80, 'circle');

        $table->updateLayout($layout);

        /** @var TableLayoutUpdated $event */
        $event = $table->pullDomainEvents()[0];
        $this->assertNull($event->auditBefore());
        $this->assertSame($layout->toArray(), $event->auditAfter());
    }

    public function test_update_layout_before_contains_previous_values(): void
    {
        $table = Table::fromPersistence(
            id: self::TABLE_ID,
            zoneId: self::ZONE_ID,
            name: 'Mesa 1',
            mergedTableGroupId: null,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            posX: 50,
            posY: 50,
            width: 80,
            height: 60,
            shape: 'rect',
        );

        $table->updateLayout(TableLayout::create(300, 200, 100, 70, 'circle'));

        /** @var TableLayoutUpdated $event */
        $event = $table->pullDomainEvents()[0];
        $this->assertSame(['pos_x' => 50, 'pos_y' => 50, 'width' => 80, 'height' => 60, 'shape' => 'rect'], $event->auditBefore());
    }
}
