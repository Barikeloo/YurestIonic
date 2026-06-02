<?php

namespace Tests\Unit\Zone;

use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Zone\Application\CreateZone\CreateZone;
use App\Zone\Application\CreateZone\CreateZoneCommand;
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
        $auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $repository->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (Zone $zone): bool {
                return $zone->name()->value() === 'Salon';
            }));

        $auditRecorder->shouldReceive('record')
            ->once();

        $createZone = new CreateZone($repository, $auditRecorder);

        $response = $createZone(new CreateZoneCommand(
            name: 'Salon',
            restaurantId: '00000000-0000-4000-8000-000000000000',
        ));

        $this->assertInstanceOf(CreateZoneResponse::class, $response);
        $this->assertSame('Salon', $response->name);
    }
}
