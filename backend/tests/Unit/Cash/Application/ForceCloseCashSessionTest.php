<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\ForceCloseCashSession\ForceCloseCashSession;
use App\Cash\Application\ForceCloseCashSession\ForceCloseCashSessionCommand;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Event\CashSessionForceClosed;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ForceCloseCashSessionTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private EventBusInterface&MockInterface $eventBus;
    private ForceCloseCashSession $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);

        $this->useCase = new ForceCloseCashSession(
            $this->cashSessionRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_force_closes_session(): void
    {
        $sessionId = Uuid::generate()->value();
        $closedByUserId = Uuid::generate()->value();

        $command = new ForceCloseCashSessionCommand(
            cashSessionId: $sessionId,
            closedByUserId: $closedByUserId,
        );

        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->cashSessionRepository
            ->shouldReceive('save')
            ->once()
            ->with($session);

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(CashSessionForceClosed::class));

        $response = ($this->useCase)($command);

        $this->assertSame('abandoned', $response->status);
        $this->assertSame($session->uuid()->value(), $response->uuid);
    }

    public function test_throws_exception_when_session_not_found(): void
    {
        $command = new ForceCloseCashSessionCommand(
            cashSessionId: Uuid::generate()->value(),
            closedByUserId: Uuid::generate()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->cashSessionRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(CashSessionNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_records_audit_with_correct_slug(): void
    {
        $command = new ForceCloseCashSessionCommand(
            cashSessionId: Uuid::generate()->value(),
            closedByUserId: Uuid::generate()->value(),
        );

        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->andReturn($session);

        $this->cashSessionRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function (object $event): bool {
                return $event instanceof CashSessionForceClosed
                    && $event->auditSlug() === 'caja.force_closed';
            }));

        ($this->useCase)($command);

        $this->assertTrue(true);
    }
}
