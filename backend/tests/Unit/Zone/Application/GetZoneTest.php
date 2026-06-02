<?php

namespace Tests\Unit\Zone\Application;

use App\Zone\Application\GetZone\GetZone;
use App\Zone\Application\GetZone\GetZoneCommand;
use App\Zone\Application\GetZone\GetZoneResponse;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;
use Mockery;
use PHPUnit\Framework\TestCase;

class GetZoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_zone_when_found(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);

        $zone = Zone::dddCreate(ZoneName::create('Salon principal'));

        $repository->shouldReceive('findById')
            ->once()
            ->with($zone->id()->value())
            ->andReturn($zone);

        $useCase = new GetZone($repository);

        $response = $useCase(new GetZoneCommand(id: $zone->id()->value()));

        $this->assertInstanceOf(GetZoneResponse::class, $response);
        $this->assertSame('Salon principal', $response->name);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $useCase = new GetZone($repository);

        $this->expectException(ZoneNotFoundException::class);

        $useCase(new GetZoneCommand(id: 'non-existent-id'));
    }
}
