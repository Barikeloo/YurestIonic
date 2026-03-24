<?php

namespace Tests\Unit\Zone;

use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\ValueObject\ZoneName;
use PHPUnit\Framework\TestCase;

class ZoneEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_and_allows_rename(): void
    {
        $zone = Zone::dddCreate(ZoneName::create('Salon'));

        $this->assertSame('Salon', $zone->name());

        $zone->rename(ZoneName::create('Terraza'));

        $this->assertSame('Terraza', $zone->name());
    }
}
