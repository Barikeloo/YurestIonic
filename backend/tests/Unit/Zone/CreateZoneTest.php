<?php

namespace Tests\Unit\Zone;

use App\Shared\Application\Event\EventBusInterface;
use App\Zone\Application\CreateZone\CreateZone;
use App\Zone\Application\CreateZone\CreateZoneCommand;
use App\Zone\Application\CreateZone\CreateZoneResponse;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Event\ZoneCreated;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class CreateZoneTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function test_invoke_creates_zone_and_publishes_event(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Zone $zone): bool => $zone->name()->value() === 'Salon'
        ));

        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(ZoneCreated::class));

        $createZone = new CreateZone($repository, $eventBus);

        $response = $createZone(new CreateZoneCommand(name: 'Salon'));

        $this->assertInstanceOf(CreateZoneResponse::class, $response);
        $this->assertSame('Salon', $response->name);
    }
}
