<?php

namespace Tests\Unit\Zone;

use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Event\ZoneCreated;
use App\Zone\Domain\Event\ZoneDeleted;
use App\Zone\Domain\Event\ZoneUpdated;
use App\Zone\Domain\ValueObject\ZoneName;
use PHPUnit\Framework\TestCase;

class ZoneEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_and_allows_rename(): void
    {
        $zone = Zone::dddCreate(ZoneName::create('Salon'));

        $this->assertSame('Salon', $zone->name()->value());

        $zone->rename(ZoneName::create('Terraza'));

        $this->assertSame('Terraza', $zone->name()->value());
    }

    public function test_ddd_create_records_a_zone_created_event(): void
    {
        $zone = Zone::dddCreate(ZoneName::create('Salon'));

        $events = $zone->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(ZoneCreated::class, $events[0]);
        $this->assertSame([], $zone->pullDomainEvents());
    }

    public function test_rename_records_a_zone_updated_event_with_before_after(): void
    {
        $zone = Zone::fromPersistence('00000000-0000-4000-8000-000000000000', 'Salon', new \DateTimeImmutable(), new \DateTimeImmutable());

        $zone->rename(ZoneName::create('Terraza'));

        $events = $zone->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ZoneUpdated::class, $events[0]);
        $this->assertSame(['name' => 'Salon'], $events[0]->auditBefore());
        $this->assertSame(['name' => 'Terraza'], $events[0]->auditAfter());
    }

    public function test_delete_records_a_zone_deleted_event(): void
    {
        $zone = Zone::fromPersistence('00000000-0000-4000-8000-000000000000', 'Salon', new \DateTimeImmutable(), new \DateTimeImmutable());

        $zone->delete();

        $events = $zone->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ZoneDeleted::class, $events[0]);
    }
}
