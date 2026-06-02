<?php

namespace Tests\Unit\Cash\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Application\StartClosingCashSession\StartClosingCashSession;
use App\Cash\Application\StartClosingCashSession\StartClosingCashSessionCommand;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\OpenOperationsPreventClosingException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Order\Domain\Interfaces\OrderRepositoryInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class StartClosingCashSessionTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private OrderRepositoryInterface&MockInterface $orderRepository;
    private TransactionManagerInterface&MockInterface $transactionManager;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private StartClosingCashSession $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new StartClosingCashSession(
            $this->cashSessionRepository,
            $this->orderRepository,
            $this->transactionManager,
            $this->auditRecorder,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_starts_closing_successfully(): void
    {
        $sessionId = Uuid::generate()->value();

        $command = new StartClosingCashSessionCommand(
            cashSessionId: $sessionId,
        );

        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->orderRepository
            ->shouldReceive('countActiveByRestaurantId')
            ->once()
            ->andReturn(0);

        $this->cashSessionRepository
            ->shouldReceive('save')
            ->once()
            ->with($session);

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type(AuditEventDraft::class));

        $response = ($this->useCase)($command);

        $this->assertSame($session->id()->value(), $response->id);
        $this->assertSame('closing', $response->status);
    }

    public function test_throws_exception_when_session_not_found(): void
    {
        $command = new StartClosingCashSessionCommand(
            cashSessionId: Uuid::generate()->value(),
        );

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->orderRepository->shouldNotReceive('countActiveByRestaurantId');
        $this->cashSessionRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(CashSessionNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_throws_exception_when_active_orders_exist(): void
    {
        $restaurantId = Uuid::generate();

        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: $restaurantId,
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $command = new StartClosingCashSessionCommand(
            cashSessionId: $session->id()->value(),
        );

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->orderRepository
            ->shouldReceive('countActiveByRestaurantId')
            ->once()
            ->with($restaurantId)
            ->andReturn(3);

        $this->cashSessionRepository->shouldNotReceive('save');
        $this->auditRecorder->shouldNotReceive('record');

        $this->expectException(OpenOperationsPreventClosingException::class);

        ($this->useCase)($command);
    }

    public function test_records_audit_with_correct_slug(): void
    {
        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $command = new StartClosingCashSessionCommand(
            cashSessionId: $session->id()->value(),
        );

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->andReturn($session);

        $this->orderRepository
            ->shouldReceive('countActiveByRestaurantId')
            ->andReturn(0);

        $this->cashSessionRepository
            ->shouldReceive('save')
            ->once();

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft): bool {
                return $draft->slug->equals(ActionSlug::create('caja.closing_started'));
            }));

        ($this->useCase)($command);

        $this->assertTrue(true);
    }
}
