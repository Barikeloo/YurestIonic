<?php

namespace Tests\Unit\Tables;

use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\Entity\Table;
use App\Tables\Domain\Event\TableCreated;
use App\Tables\Domain\Event\TableDeleted;
use App\Tables\Domain\Event\TableUpdated;
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
}
