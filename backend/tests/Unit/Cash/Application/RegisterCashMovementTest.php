<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\RegisterCashMovement\RegisterCashMovement;
use App\Cash\Application\RegisterCashMovement\RegisterCashMovementCommand;
use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Event\CashMovementRegistered;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\CashSessionNotOpenForMovementException;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class RegisterCashMovementTest extends TestCase
{
    private CashMovementRepositoryInterface&MockInterface $movementRepository;
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private EventBusInterface&MockInterface $eventBus;
    private RegisterCashMovement $useCase;

    protected function setUp(): void
    {
        $this->movementRepository = Mockery::mock(CashMovementRepositoryInterface::class);
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);

        $this->useCase = new RegisterCashMovement(
            $this->movementRepository,
            $this->cashSessionRepository,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_registers_movement_successfully(): void
    {
        $sessionId = Uuid::generate()->value();
        $userId = Uuid::generate()->value();

        $command = new RegisterCashMovementCommand(
            restaurantId: Uuid::generate()->value(),
            cashSessionId: $sessionId,
            type: 'in',
            reasonCode: 'change_refill',
            amountCents: 20000,
            userId: $userId,
            description: 'Cambio para el turno',
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

        $this->movementRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(CashMovement::class));

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(CashMovementRegistered::class));

        $response = ($this->useCase)($command);

        $this->assertSame($sessionId, $response->cashSessionId);
        $this->assertSame('in', $response->type);
        $this->assertSame('change_refill', $response->reasonCode);
        $this->assertSame(20000, $response->amountCents);
    }

    public function test_throws_exception_when_session_not_found(): void
    {
        $command = new RegisterCashMovementCommand(
            restaurantId: Uuid::generate()->value(),
            cashSessionId: Uuid::generate()->value(),
            type: 'in',
            reasonCode: 'other',
            amountCents: 1000,
            userId: Uuid::generate()->value(),
            description: null,
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->movementRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(CashSessionNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_throws_exception_when_session_not_open(): void
    {
        $sessionId = Uuid::generate()->value();

        $command = new RegisterCashMovementCommand(
            restaurantId: Uuid::generate()->value(),
            cashSessionId: $sessionId,
            type: 'in',
            reasonCode: 'other',
            amountCents: 1000,
            userId: Uuid::generate()->value(),
            description: null,
        );

        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );
        $session->startClosing();

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->movementRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(CashSessionNotOpenForMovementException::class);

        ($this->useCase)($command);
    }

    public function test_records_audit_with_correct_slug(): void
    {
        $sessionId = Uuid::generate()->value();

        $command = new RegisterCashMovementCommand(
            restaurantId: Uuid::generate()->value(),
            cashSessionId: $sessionId,
            type: 'out',
            reasonCode: 'sangria',
            amountCents: 5000,
            userId: Uuid::generate()->value(),
            description: null,
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

        $this->movementRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function (object $event): bool {
                return $event instanceof CashMovementRegistered
                    && $event->auditSlug() === 'caja.cash_movement';
            }));

        ($this->useCase)($command);

        $this->assertTrue(true);
    }
}
