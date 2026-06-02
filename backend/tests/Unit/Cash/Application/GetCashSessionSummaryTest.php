<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\GetCashSessionSummary\GetCashSessionSummary;
use App\Cash\Application\GetCashSessionSummary\GetCashSessionSummaryCommand;
use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Sale\Domain\Entity\SalePayment;
use App\Sale\Domain\ValueObject\PaymentMethod;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetCashSessionSummaryTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private CashMovementRepositoryInterface&MockInterface $cashMovementRepository;
    private SalePaymentRepositoryInterface&MockInterface $salePaymentRepository;
    private GetCashSessionSummary $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->cashMovementRepository = Mockery::mock(CashMovementRepositoryInterface::class);
        $this->salePaymentRepository = Mockery::mock(SalePaymentRepositoryInterface::class);

        $this->useCase = new GetCashSessionSummary(
            $this->cashSessionRepository,
            $this->cashMovementRepository,
            $this->salePaymentRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_summary_with_movements_and_payments(): void
    {
        $sessionUuid = Uuid::generate();
        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $command = new GetCashSessionSummaryCommand(
            cashSessionId: $session->id()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $inMovement = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            type: MovementType::in(),
            reasonCode: MovementReasonCode::changeRefill(),
            amount: Money::create(10000),
            userId: Uuid::generate(),
        );

        $outMovement = CashMovement::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            type: MovementType::out(),
            reasonCode: MovementReasonCode::sangria(),
            amount: Money::create(5000),
            userId: Uuid::generate(),
        );

        $this->cashMovementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([$inMovement, $outMovement]);

        $cashPayment = SalePayment::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            saleId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            method: PaymentMethod::create('cash'),
            amount: Money::create(30000),
            userId: Uuid::generate(),
        );

        $cardPayment = SalePayment::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            saleId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            method: PaymentMethod::create('card'),
            amount: Money::create(15000),
            userId: Uuid::generate(),
        );

        $this->salePaymentRepository
            ->shouldReceive('findNonCancelledByCashSessionId')
            ->once()
            ->andReturn([$cashPayment, $cardPayment]);

        $response = ($this->useCase)($command);

        $this->assertSame($session->id()->value(), $response->id);
        $this->assertSame(45000, $response->totalSales);
        $this->assertSame(30000, $response->totalCashPayments);
        $this->assertSame(15000, $response->totalCardPayments);
        $this->assertSame(10000, $response->totalInMovements);
        $this->assertSame(5000, $response->totalOutMovements);
        $this->assertSame(2, $response->movementsCount);
        $this->assertSame(2, $response->paymentsCount);
        $this->assertSame(2, $response->ticketsCount);
        $this->assertSame(85000, $response->expectedAmount);
    }

    public function test_returns_summary_without_movements_or_payments(): void
    {
        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );

        $command = new GetCashSessionSummaryCommand(
            cashSessionId: $session->id()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn($session);

        $this->cashMovementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $this->salePaymentRepository
            ->shouldReceive('findNonCancelledByCashSessionId')
            ->once()
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertSame($session->id()->value(), $response->id);
        $this->assertSame(0, $response->totalSales);
        $this->assertSame(0, $response->totalInMovements);
        $this->assertSame(0, $response->totalOutMovements);
        $this->assertSame(50000, $response->expectedAmount);
        $this->assertSame(0, $response->movementsCount);
        $this->assertSame(0, $response->paymentsCount);
        $this->assertSame(0, $response->ticketsCount);
    }

    public function test_throws_exception_when_session_not_found(): void
    {
        $command = new GetCashSessionSummaryCommand(
            cashSessionId: Uuid::generate()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->expectException(CashSessionNotFoundException::class);

        ($this->useCase)($command);
    }
}
