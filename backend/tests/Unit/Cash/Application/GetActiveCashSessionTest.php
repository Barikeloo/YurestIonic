<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\GetActiveCashSession\GetActiveCashSession;
use App\Cash\Application\GetActiveCashSession\GetActiveCashSessionCommand;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetActiveCashSessionTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private GetActiveCashSession $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);

        $this->useCase = new GetActiveCashSession(
            $this->cashSessionRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_active_session(): void
    {
        $restaurantId = Uuid::generate()->value();
        $deviceId = 'device-abc';

        $command = new GetActiveCashSessionCommand(
            restaurantId: $restaurantId,
            deviceId: $deviceId,
        );

        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            deviceId: DeviceId::create($deviceId),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $this->cashSessionRepository
            ->shouldReceive('findActiveByDeviceId')
            ->once()
            ->andReturn($session);

        $response = ($this->useCase)($command);

        $this->assertNotNull($response);
        $this->assertSame($restaurantId, $response->restaurantId);
        $this->assertSame($deviceId, $response->deviceId);
        $this->assertSame('open', $response->status);
    }

    public function test_returns_null_when_no_active_session(): void
    {
        $command = new GetActiveCashSessionCommand(
            restaurantId: Uuid::generate()->value(),
            deviceId: 'device-abc',
        );

        $this->cashSessionRepository
            ->shouldReceive('findActiveByDeviceId')
            ->once()
            ->andReturn(null);

        $response = ($this->useCase)($command);

        $this->assertNull($response);
    }
}
