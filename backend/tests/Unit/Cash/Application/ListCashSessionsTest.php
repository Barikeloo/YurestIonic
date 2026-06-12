<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\ListCashSessions\ListCashSessions;
use App\Cash\Application\ListCashSessions\ListCashSessionsCommand;
use App\Cash\Domain\Entity\CashMovement;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Interfaces\CashMovementRepositoryInterface;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\Interfaces\SalePaymentRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Cash\Domain\ValueObject\MovementReasonCode;
use App\Cash\Domain\ValueObject\MovementType;
use App\Cash\Domain\ValueObject\ZReportHash;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Sale\Domain\Entity\SalePayment;
use App\Sale\Domain\ValueObject\PaymentMethod;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ListCashSessionsTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private CashMovementRepositoryInterface&MockInterface $cashMovementRepository;
    private SalePaymentRepositoryInterface&MockInterface $salePaymentRepository;
    private UserRepositoryInterface&MockInterface $userRepository;
    private ListCashSessions $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->cashMovementRepository = Mockery::mock(CashMovementRepositoryInterface::class);
        $this->salePaymentRepository = Mockery::mock(SalePaymentRepositoryInterface::class);
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->userRepository->shouldReceive('findById')->andReturn(null);

        $this->useCase = new ListCashSessions(
            $this->cashSessionRepository,
            $this->cashMovementRepository,
            $this->salePaymentRepository,
            $this->userRepository,
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    private function createClosedSession(): CashSession
    {
        $session = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            deviceId: DeviceId::create('device-1'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(50000),
        );
        $session->startClosing();
        $session->close(
            closedByUserId: Uuid::generate(),
            finalAmount: Money::create(120000),
            expectedAmount: Money::create(115000),
            discrepancy: Money::create(5000),
            zReportNumber: ZReportNumber::create(1),
            zReportHash: ZReportHash::create(hash('sha256', 'test')),
        );

        return $session;
    }

    public function test_returns_list_of_sessions(): void
    {
        $restaurantId = Uuid::generate()->value();
        $command = new ListCashSessionsCommand(
            restaurantId: $restaurantId,
        );

        $session = $this->createClosedSession();

        $this->cashSessionRepository
            ->shouldReceive('findByRestaurantId')
            ->once()
            ->andReturn([$session]);

        $this->cashMovementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->with($session->uuid())
            ->andReturn([]);

        $payment = SalePayment::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            saleId: Uuid::generate(),
            cashSessionId: $session->uuid(),
            method: PaymentMethod::create('cash'),
            amount: Money::create(100000),
            userId: Uuid::generate(),
        );

        $this->salePaymentRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->with($session->uuid())
            ->andReturn([$payment]);

        $response = ($this->useCase)($command);

        $this->assertCount(1, $response->sessions);
        $this->assertSame($session->uuid()->value(), $response->sessions[0]->uuid);
        $this->assertSame('closed', $response->sessions[0]->status);
        $this->assertSame(100000, $response->sessions[0]->gross);
        $this->assertSame(0, $response->sessions[0]->movIn);
        $this->assertSame(0, $response->sessions[0]->movOut);
        $this->assertSame(1, $response->sessions[0]->tickets);
    }

    public function test_returns_empty_list_when_no_sessions(): void
    {
        $command = new ListCashSessionsCommand(
            restaurantId: Uuid::generate()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findByRestaurantId')
            ->once()
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertCount(0, $response->sessions);
    }

    public function test_aggregates_movements_in_list(): void
    {
        $restaurantId = Uuid::generate()->value();
        $command = new ListCashSessionsCommand(
            restaurantId: $restaurantId,
        );

        $session = $this->createClosedSession();

        $this->cashSessionRepository
            ->shouldReceive('findByRestaurantId')
            ->once()
            ->andReturn([$session]);

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
            amount: Money::create(3000),
            userId: Uuid::generate(),
        );

        $this->cashMovementRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([$inMovement, $outMovement]);

        $this->salePaymentRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertCount(1, $response->sessions);
        $this->assertSame(10000, $response->sessions[0]->movIn);
        $this->assertSame(3000, $response->sessions[0]->movOut);
        $this->assertSame(0, $response->sessions[0]->gross);
    }
}
