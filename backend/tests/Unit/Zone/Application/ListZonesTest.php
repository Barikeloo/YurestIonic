<?php

namespace Tests\Unit\Zone\Application;

use App\Zone\Application\ListZones\ListZones;
use App\Zone\Application\ListZones\ListZonesCommand;
use App\Zone\Application\ListZones\ListZonesResponse;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;
use Mockery;
use PHPUnit\Framework\TestCase;

class ListZonesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_all_zones(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);

        $zone1 = Zone::dddCreate(ZoneName::create('Salon'));
        $zone2 = Zone::dddCreate(ZoneName::create('Terraza'));

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([$zone1, $zone2]);

        $useCase = new ListZones($repository);

        $response = $useCase(new ListZonesCommand(includeDeleted: false));

        $this->assertInstanceOf(ListZonesResponse::class, $response);
        $this->assertCount(2, $response->toArray());
        $this->assertSame('Salon', $response->toArray()[0]['name']);
        $this->assertSame('Terraza', $response->toArray()[1]['name']);
    }

    public function test_includes_deleted_when_flag_is_true(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);

        $zone1 = Zone::dddCreate(ZoneName::create('Salon'));
        $zone2 = Zone::dddCreate(ZoneName::create('Deleted zone'));

        $repository->shouldReceive('findAll')
            ->once()
            ->with(true)
            ->andReturn([$zone1, $zone2]);

        $useCase = new ListZones($repository);

        $response = $useCase(new ListZonesCommand(includeDeleted: true));

        $this->assertCount(2, $response->toArray());
    }

    public function test_returns_empty_list(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);

        $repository->shouldReceive('findAll')
            ->once()
            ->with(false)
            ->andReturn([]);

        $useCase = new ListZones($repository);

        $response = $useCase(new ListZonesCommand(includeDeleted: false));

        $this->assertInstanceOf(ListZonesResponse::class, $response);
        $this->assertEmpty($response->toArray());
    }
}
