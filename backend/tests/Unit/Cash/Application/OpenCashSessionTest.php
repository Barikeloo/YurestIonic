<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\OpenCashSession\OpenCashSession;
use App\Cash\Application\OpenCashSession\OpenCashSessionCommand;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Event\CashSessionOpened;
use App\Cash\Domain\Exception\ActiveCashSessionAlreadyExistsException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class OpenCashSessionTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private EventBusInterface&MockInterface $eventBus;
    private OpenCashSession $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);

        $this->useCase = new OpenCashSession(
            $this->cashSessionRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_opens_session_successfully(): void
    {
        $restaurantId = Uuid::generate()->value();
        $userId = Uuid::generate()->value();

        $command = new OpenCashSessionCommand(
            restaurantId: $restaurantId,
            deviceId: 'device-abc',
            openedByUserId: $userId,
            initialAmountCents: 50000,
            notes: 'Turno mañana',
        );

        $this->cashSessionRepository
            ->shouldReceive('findActiveByDeviceId')
            ->once()
            ->andReturn(null);

        $this->cashSessionRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(CashSession::class));

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(CashSessionOpened::class));

        $response = ($this->useCase)($command);

        $this->assertSame('open', $response->status);
        $this->assertSame($restaurantId, $response->restaurantId);
        $this->assertSame('device-abc', $response->deviceId);
        $this->assertSame($userId, $response->openedByUserId);
        $this->assertSame(50000, $response->initialAmountCents);
        $this->assertSame('Turno mañana', $response->notes);
    }

    public function test_throws_exception_when_active_session_exists(): void
    {
        $command = new OpenCashSessionCommand(
            restaurantId: Uuid::generate()->value(),
            deviceId: 'device-abc',
            openedByUserId: Uuid::generate()->value(),
            initialAmountCents: 50000,
            notes: null,
        );

        $existingSession = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($command->restaurantId),
            deviceId: \App\Cash\Domain\ValueObject\DeviceId::create('device-abc'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $this->cashSessionRepository
            ->shouldReceive('findActiveByDeviceId')
            ->once()
            ->andReturn($existingSession);

        $this->cashSessionRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(ActiveCashSessionAlreadyExistsException::class);

        ($this->useCase)($command);
    }

    public function test_opens_without_notes(): void
    {
        $command = new OpenCashSessionCommand(
            restaurantId: Uuid::generate()->value(),
            deviceId: 'device-xyz',
            openedByUserId: Uuid::generate()->value(),
            initialAmountCents: 0,
            notes: null,
        );

        $this->cashSessionRepository
            ->shouldReceive('findActiveByDeviceId')
            ->once()
            ->andReturn(null);

        $this->cashSessionRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(CashSessionOpened::class));

        $response = ($this->useCase)($command);

        $this->assertNull($response->notes);
    }
}
