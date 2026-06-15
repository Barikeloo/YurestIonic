<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\GenerateZReport\GenerateZReport;
use App\Cash\Application\GenerateZReport\GenerateZReportCommand;
use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Entity\ZReport;
use App\Cash\Domain\Event\ZReportGenerated;
use App\Cash\Domain\Exception\CashSessionCannotGenerateZReportException;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Exception\ZReportAlreadyExistsException;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\Interfaces\TipRepositoryInterface;
use App\Cash\Domain\Interfaces\ZReportRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Entity\SalePayment;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Sale\Domain\ValueObject\PaymentMethod;
use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GenerateZReportTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private SalePaymentRepositoryInterface&MockInterface $salePaymentRepository;
    private CashMovementRepositoryInterface&MockInterface $cashMovementRepository;
    private TipRepositoryInterface&MockInterface $tipRepository;
    private ZReportRepositoryInterface&MockInterface $zReportRepository;
    private SaleRepositoryInterface&MockInterface $saleRepository;
    private EventBusInterface&MockInterface $eventBus;
    private GenerateZReport $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->salePaymentRepository = Mockery::mock(SalePaymentRepositoryInterface::class);
        $this->cashMovementRepository = Mockery::mock(CashMovementRepositoryInterface::class);
        $this->tipRepository = Mockery::mock(TipRepositoryInterface::class);
        $this->zReportRepository = Mockery::mock(ZReportRepositoryInterface::class);
        $this->saleRepository = Mockery::mock(SaleRepositoryInterface::class);
        $this->eventBus = Mockery::mock(EventBusInterface::class);

        $this->useCase = new GenerateZReport(
            $this->cashSessionRepository,
            $this->salePaymentRepository,
            $this->cashMovementRepository,
            $this->tipRepository,
            $this->zReportRepository,
            $this->saleRepository,
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

    public function test_generates_z_report_with_final_amount(): void
    {
        $session = $this->createClosingSession();

        $command = new GenerateZReportCommand(
            cashSessionId: $session->id()->value(),
            finalAmountCents: 120000,
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->zReportRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn(null);

        $payment = SalePayment::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            saleId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            method: PaymentMethod::create('cash'),
            amount: Money::create(80000),
            userId: Uuid::generate(),
        );

        $this->salePaymentRepository
            ->shouldReceive('findNonCancelledByCashSessionId')
            ->once()
            ->andReturn([$payment]);

        $this->cashMovementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->tipRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $restaurantId = $session->restaurantId();
        $this->zReportRepository
            ->shouldReceive('nextReportNumber')
            ->once()
            ->with($restaurantId)
            ->andReturn(ZReportNumber::create(5));

        $this->zReportRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::type(ZReport::class));

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(ZReportGenerated::class));

        $response = ($this->useCase)($command);

        $this->assertSame(5, $response->reportNumber);
        $this->assertSame($session->id()->value(), $response->cashSessionId);
    }

    public function test_throws_exception_when_session_not_found(): void
    {
        $command = new GenerateZReportCommand(
            cashSessionId: Uuid::generate()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->expectException(CashSessionNotFoundException::class);

        ($this->useCase)($command);
    }

    public function test_throws_exception_when_session_not_closing_or_closed(): void
    {
        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $command = new GenerateZReportCommand(
            cashSessionId: $session->id()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->expectException(CashSessionCannotGenerateZReportException::class);

        ($this->useCase)($command);
    }

    public function test_throws_exception_when_z_report_already_exists(): void
    {
        $session = $this->createClosingSession();
        $existingZReport = ZReport::generate(
            restaurantId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            reportNumber: ZReportNumber::create(1),
            totalSales: Money::create(0),
            totalCash: Money::create(0),
            totalCard: Money::create(0),
            totalOther: Money::create(0),
            cashIn: Money::create(0),
            cashOut: Money::create(0),
            tips: Money::create(0),
            discrepancy: Money::create(0),
            salesCount: 0,
            cancelledSalesCount: 0,
        );

        $command = new GenerateZReportCommand(
            cashSessionId: $session->id()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->zReportRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn($existingZReport);

        $this->expectException(ZReportAlreadyExistsException::class);

        ($this->useCase)($command);
    }

    public function test_throws_exception_when_final_amount_required(): void
    {
        $session = $this->createClosingSession();

        $command = new GenerateZReportCommand(
            cashSessionId: $session->id()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->zReportRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn(null);

        $this->expectException(CashSessionCannotGenerateZReportException::class);

        ($this->useCase)($command);
    }

    public function test_generates_z_report_with_cash_card_and_other_payments(): void
    {
        $session = $this->createClosingSession();

        $command = new GenerateZReportCommand(
            cashSessionId: $session->id()->value(),
            finalAmountCents: 150000,
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->zReportRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn(null);

        $cashPayment = SalePayment::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            saleId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            method: PaymentMethod::create('cash'),
            amount: Money::create(50000),
            userId: Uuid::generate(),
        );

        $cardPayment = SalePayment::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            saleId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            method: PaymentMethod::create('card'),
            amount: Money::create(30000),
            userId: Uuid::generate(),
        );

        $bizumPayment = SalePayment::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            saleId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            method: PaymentMethod::create('bizum'),
            amount: Money::create(20000),
            userId: Uuid::generate(),
        );

        $this->salePaymentRepository
            ->shouldReceive('findNonCancelledByCashSessionId')
            ->once()
            ->andReturn([$cashPayment, $cardPayment, $bizumPayment]);

        $this->cashMovementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->tipRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->zReportRepository
            ->shouldReceive('nextReportNumber')
            ->once()
            ->andReturn(ZReportNumber::create(1));

        $this->zReportRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(ZReportGenerated::class));

        $response = ($this->useCase)($command);

        $this->assertSame(100000, $response->totalSalesCents);
        $this->assertSame(50000, $response->totalCashCents);
        $this->assertSame(30000, $response->totalCardCents);
        $this->assertSame(20000, $response->totalOtherCents);
    }

    public function test_aggregates_movements_and_tips(): void
    {
        $session = $this->createClosingSession();
        $sessionUuid = $session->uuid();

        $command = new GenerateZReportCommand(
            cashSessionId: $session->id()->value(),
            finalAmountCents: 130000,
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->zReportRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn(null);

        $this->salePaymentRepository
            ->shouldReceive('findNonCancelledByCashSessionId')
            ->once()
            ->andReturn([]);

        $inMovement = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            cashSessionId: $sessionUuid,
            type: MovementType::in(),
            reasonCode: MovementReasonCode::changeRefill(),
            amount: Money::create(10000),
            userId: Uuid::generate(),
        );

        $outMovement = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            cashSessionId: $sessionUuid,
            type: MovementType::out(),
            reasonCode: MovementReasonCode::sangria(),
            amount: Money::create(5000),
            userId: Uuid::generate(),
        );

        $this->cashMovementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([$inMovement, $outMovement]);

        $this->tipRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->zReportRepository
            ->shouldReceive('nextReportNumber')
            ->once()
            ->andReturn(ZReportNumber::create(1));

        $this->zReportRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(ZReportGenerated::class));

        $response = ($this->useCase)($command);

        $this->assertSame(10000, $response->cashInCents);
        $this->assertSame(5000, $response->cashOutCents);
        $this->assertSame(0, $response->tipsCents);
    }

    public function test_counts_sales_and_cancelled_sales(): void
    {
        $session = $this->createClosingSession();

        $command = new GenerateZReportCommand(
            cashSessionId: $session->id()->value(),
            finalAmountCents: 50000,
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->zReportRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn(null);

        $this->salePaymentRepository
            ->shouldReceive('findNonCancelledByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->cashMovementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->tipRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $cashSessionUuid = $session->uuid()->value();
        $normalSale = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            orderId: Uuid::generate(),
            openedByUserId: Uuid::generate(),
        );

        $cancelledSale = Sale::fromPersistence(
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
            cashSessionId: $cashSessionUuid,
            status: 'cancelled',
        );

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([$normalSale, $normalSale, $cancelledSale]);

        $this->zReportRepository
            ->shouldReceive('nextReportNumber')
            ->once()
            ->andReturn(ZReportNumber::create(1));

        $this->zReportRepository
            ->shouldReceive('save')
            ->once();

        $this->eventBus
            ->shouldReceive('publish')
            ->once()
            ->with(Mockery::type(ZReportGenerated::class));

        $response = ($this->useCase)($command);

        $this->assertSame(3, $response->salesCount);
        $this->assertSame(1, $response->cancelledSalesCount);
    }
}
