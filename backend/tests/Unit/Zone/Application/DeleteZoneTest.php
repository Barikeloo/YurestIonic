<?php

namespace Tests\Unit\Zone\Application;

use App\Shared\Application\Event\EventBusInterface;
use App\Zone\Application\DeleteZone\DeleteZone;
use App\Zone\Application\DeleteZone\DeleteZoneCommand;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Event\ZoneDeleted;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class DeleteZoneTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ZONE_ID = '00000000-0000-4000-8000-000000000000';

    public function test_deletes_zone_and_publishes_event(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $zone = Zone::fromPersistence(self::ZONE_ID, 'To delete', new \DateTimeImmutable(), new \DateTimeImmutable());

        $repository->shouldReceive('findById')->once()->with(self::ZONE_ID)->andReturn($zone);
        $repository->shouldReceive('deleteById')->once()->with(self::ZONE_ID)->andReturnTrue();
        $eventBus->shouldReceive('publish')->once()->with(Mockery::type(ZoneDeleted::class));

        $useCase = new DeleteZone($repository, $eventBus);

        $useCase(new DeleteZoneCommand(id: self::ZONE_ID));
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $eventBus = Mockery::mock(EventBusInterface::class);

        $repository->shouldReceive('findById')->once()->with('non-existent-id')->andReturn(null);
        $repository->shouldNotReceive('deleteById');
        $eventBus->shouldNotReceive('publish');

        $useCase = new DeleteZone($repository, $eventBus);

        $this->expectException(ZoneNotFoundException::class);

        $useCase(new DeleteZoneCommand(id: 'non-existent-id'));
    }
}
