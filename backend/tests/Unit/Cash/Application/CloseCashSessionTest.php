<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\CloseCashSession\CloseCashSession;
use App\Cash\Application\CloseCashSession\CloseCashSessionCommand;
use App\Cash\Application\GenerateZReport\GenerateZReport;
use App\Cash\Application\GenerateZReport\GenerateZReportCommand;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Event\CashSessionClosed;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\PendingSalesPreventClosingException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Cash\Domain\ValueObject\ZReportHash;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\Interfaces\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class CloseCashSessionTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private GenerateZReport&MockInterface $generateZReport;
    private SaleRepositoryInterface&MockInterface $saleRepository;
    private TransactionManagerInterface&MockInterface $transactionManager;
    private EventBusInterface&MockInterface $eventBus;
    private CloseCashSession $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->generateZReport = Mockery::mock(GenerateZReport::class);
        $this->saleRepository = Mockery::mock(SaleRepositoryInterface::class);
        $this->transactionManager = Mockery::mock(TransactionManagerInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);

        $this->useCase = new CloseCashSession(
            $this->cashSessionRepository,
            $this->generateZReport,
            $this->saleRepository,
            $this->transactionManager,
            $this->eventBus,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function createClosingSession(): CashSession
    {
        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );
        $session->startClosing();

        return $session;
    }

    private function createZReport(): \App\Cash\Domain\Entity\ZReport
    {
        return \App\Cash\Domain\Entity\ZReport::generate(
            restaurantId: Uuid::generate(),
            cashSessionId: Uuid::generate(),
            reportNumber: ZReportNumber::create(3),
            totalSales: Money::create(100000),
            totalCash: Money::create(70000),
            totalCard: Money::create(30000),
            totalOther: Money::create(0),
            cashIn: Money::create(0),
            cashOut: Money::create(0),
            tips: Money::create(0),
            discrepancy: Money::create(0),
            salesCount: 5,
            cancelledSalesCount: 0,
        );
    }

    public function test_closes_session_successfully(): void
    {
        $session = $this->createClosingSession();
        $sessionId = $session->id()->value();
        $closedByUserId = Uuid::generate()->value();

        $command = new CloseCashSessionCommand(
            cashSessionId: $sessionId,
            closedByUserId: $closedByUserId,
            finalAmountCents: 120000,
            discrepancyReason: null,
        );

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $closedSale = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            orderId: Uuid::generate(),
            openedByUserId: Uuid::generate(),
        );

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([$closedSale]);

        $zReport = $this->createZReport();
        $this->generateZReport
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GenerateZReportCommand::class))
            ->andReturn(\App\Cash\Application\GenerateZReport\GenerateZReportResponse::create($zReport));

        $this->cashSessionRepository
            ->shouldReceive('save')
            ->once()
            ->with($session);

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(CashSessionClosed::class));

        $response = ($this->useCase)($command);

        $this->assertSame('closed', $response->status);
        $this->assertSame(3, $response->zReportNumber);
    }

    public function test_throws_exception_when_session_not_found(): void
    {
        $command = new CloseCashSessionCommand(
            cashSessionId: Uuid::generate()->value(),
            closedByUserId: Uuid::generate()->value(),
            finalAmountCents: 50000,
            discrepancyReason: null,
        );

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->saleRepository->shouldNotReceive('findByCashSessionId');
        $this->generateZReport->shouldNotReceive('__invoke');
        $this->cashSessionRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(CashSessionNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_throws_exception_when_pending_sales_exist(): void
    {
        $session = $this->createClosingSession();

        $command = new CloseCashSessionCommand(
            cashSessionId: $session->id()->value(),
            closedByUserId: Uuid::generate()->value(),
            finalAmountCents: 50000,
            discrepancyReason: null,
        );

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $pendingSale = Sale::fromPersistence(
            id: Uuid::generate()->value(),
            restaurantId: Uuid::generate()->value(),
            uuid: Uuid::generate()->value(),
            orderId: Uuid::generate()->value(),
            openedByUserId: Uuid::generate()->value(),
            closedByUserId: null,
            ticketNumber: null,
            valueDate: new \DateTimeImmutable(),
            total: 0,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            cashSessionId: Uuid::generate()->value(),
            status: 'pending',
        );

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([$pendingSale]);

        $this->generateZReport->shouldNotReceive('__invoke');
        $this->cashSessionRepository->shouldNotReceive('save');
        $this->eventBus->shouldNotReceive('publish');

        $this->expectException(PendingSalesPreventClosingException::class);

        ($this->useCase)($command);
    }

    public function test_records_audit_with_correct_slug(): void
    {
        $session = $this->createClosingSession();

        $command = new CloseCashSessionCommand(
            cashSessionId: $session->id()->value(),
            closedByUserId: Uuid::generate()->value(),
            finalAmountCents: 120000,
            discrepancyReason: null,
        );

        $this->transactionManager
            ->shouldReceive('run')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->andReturn($session);

        $closedSale = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            orderId: Uuid::generate(),
            openedByUserId: Uuid::generate(),
        );

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->andReturn([$closedSale]);

        $zReport = $this->createZReport();
        $this->generateZReport
            ->shouldReceive('__invoke')
            ->andReturn(\App\Cash\Application\GenerateZReport\GenerateZReportResponse::create($zReport));

        $this->cashSessionRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::on(function (object $event): bool {
                return $event instanceof CashSessionClosed
                    && $event->auditSlug() === 'caja.closed';
            }));

        ($this->useCase)($command);

        $this->assertTrue(true);
    }
}
