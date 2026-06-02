<?php

namespace Tests\Unit\Zone\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Zone\Application\DeleteZone\DeleteZone;
use App\Zone\Application\DeleteZone\DeleteZoneCommand;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;
use Mockery;
use PHPUnit\Framework\TestCase;

class DeleteZoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_deletes_zone(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $zone = Zone::dddCreate(ZoneName::create('To delete'));

        $repository->shouldReceive('findById')
            ->once()
            ->with($zone->id()->value())
            ->andReturn($zone);

        $repository->shouldReceive('deleteById')
            ->once()
            ->with($zone->id()->value())
            ->andReturnTrue();

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new DeleteZone($repository, $auditRecorder);

        $useCase(new DeleteZoneCommand(
            id: $zone->id()->value(),
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->addToAssertionCount(1);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $repository->shouldNotReceive('deleteById');
        $auditRecorder->shouldNotReceive('record');

        $useCase = new DeleteZone($repository, $auditRecorder);

        $this->expectException(ZoneNotFoundException::class);

        $useCase(new DeleteZoneCommand(
            id: 'non-existent-id',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
