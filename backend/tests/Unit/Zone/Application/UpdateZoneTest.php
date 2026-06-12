<?php

namespace Tests\Unit\Zone\Application;

use App\Shared\Application\Event\EventBusInterface;
use App\Zone\Application\UpdateZone\UpdateZone;
use App\Zone\Application\UpdateZone\UpdateZoneCommand;
use App\Zone\Application\UpdateZone\UpdateZoneResponse;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Event\ZoneUpdated;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class UpdateZoneTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ZONE_ID = '00000000-0000-4000-8000-000000000000';

    private function existingZone(): Zone
    {
        return Zone::fromPersistence(self::ZONE_ID, 'Original', new \DateTimeImmutable(), new \DateTimeImmutable());
    }

    public function test_updates_zone_name_and_publishes_event(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);
        $zone = $this->existingZone();

        $repository->shouldReceive('findById')->once()->with(self::ZONE_ID)->andReturn($zone);
        $repository->shouldReceive('save')->once()->with(Mockery::on(
            fn (Zone $z): bool => $z->name()->value() === 'Updated'
        ));
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(ZoneUpdated::class));

        $useCase = new UpdateZone($repository, $eventBus);

        $response = $useCase(new UpdateZoneCommand(id: self::ZONE_ID, name: 'Updated'));

        $this->assertInstanceOf(UpdateZoneResponse::class, $response);
        $this->assertSame('Updated', $response->name);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('save');
        $eventBus->shouldNotReceive('publish');

        $useCase = new UpdateZone($repository, $eventBus);

        $this->expectException(ZoneNotFoundException::class);

        $useCase(new UpdateZoneCommand(id: 'non-existent-id', name: 'Updated'));
    }
}
