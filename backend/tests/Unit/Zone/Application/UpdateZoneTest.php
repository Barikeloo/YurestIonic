<?php

namespace Tests\Unit\Zone\Application;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Zone\Application\UpdateZone\UpdateZone;
use App\Zone\Application\UpdateZone\UpdateZoneCommand;
use App\Zone\Application\UpdateZone\UpdateZoneResponse;
use App\Zone\Domain\Entity\Zone;
use App\Zone\Domain\Exception\ZoneNotFoundException;
use App\Zone\Domain\Interfaces\ZoneRepositoryInterface;
use App\Zone\Domain\ValueObject\ZoneName;
use Mockery;
use PHPUnit\Framework\TestCase;

class UpdateZoneTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_updates_zone_name(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $zone = Zone::dddCreate(ZoneName::create('Original'));

        $repository->shouldReceive('findById')
            ->once()
            ->with($zone->id()->value())
            ->andReturn($zone);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(fn (Zone $z): bool => $z->name()->value() === 'Updated'));

        $auditRecorder->shouldReceive('record')
            ->once();

        $useCase = new UpdateZone($repository, $auditRecorder);

        $response = $useCase(new UpdateZoneCommand(
            id: $zone->id()->value(),
            name: 'Updated',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(UpdateZoneResponse::class, $response);
        $this->assertSame('Updated', $response->name);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $repository = Mockery::mock(ZoneRepositoryInterface::class);
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('findById')
            ->once()
            ->with('non-existent-id')
            ->andReturn(null);

        $repository->shouldNotReceive('save');
        $auditRecorder->shouldNotReceive('record');

        $useCase = new UpdateZone($repository, $auditRecorder);

        $this->expectException(ZoneNotFoundException::class);

        $useCase(new UpdateZoneCommand(
            id: 'non-existent-id',
            name: 'Updated',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));
    }
}
