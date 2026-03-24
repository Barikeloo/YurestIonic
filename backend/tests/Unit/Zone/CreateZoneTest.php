<?php

namespace Tests\Unit\Zone;

use App\Zone\Application\CreateZone\CreateZone;
use App\Zone\Application\CreateZone\CreateZoneResponse;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use Mockery;
use PHPUnit\Framework\TestCase;

class CreateZoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_invoke_creates_zone_and_saves_it(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Zone $zone): bool {
                return $zone->name() === 'Salon';
            }));

        $createZone = new CreateZone($repository);
        $response = $createZone('Salon');

        $this->assertInstanceOf(CreateZoneResponse::class, $response);
        $this->assertSame('Salon', $response->name);
    }
}
