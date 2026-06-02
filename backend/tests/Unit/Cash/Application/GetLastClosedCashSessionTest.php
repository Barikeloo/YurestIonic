<?php

namespace Tests\Unit\Cash\Application;

use App\Cash\Application\GetLastClosedCashSession\GetLastClosedCashSession;
use App\Cash\Application\GetLastClosedCashSession\GetLastClosedCashSessionCommand;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Cash\Domain\ValueObject\ZReportHash;
use App\Cash\Domain\ValueObject\ZReportNumber;
use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\Interfaces\SaleRepositoryInterface;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use App\User\Domain\Entity\User;
use App\User\Domain\Interfaces\UserRepositoryInterface;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetLastClosedCashSessionTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private UserRepositoryInterface&MockInterface $userRepository;
    private SaleRepositoryInterface&MockInterface $saleRepository;
    private GetLastClosedCashSession $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->userRepository = Mockery::mock(UserRepositoryInterface::class);
        $this->saleRepository = Mockery::mock(SaleRepositoryInterface::class);

        $this->useCase = new GetLastClosedCashSession(
            $this->cashSessionRepository,
            $this->userRepository,
            $this->saleRepository,
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
            finalAmount: Money::create(100000),
            expectedAmount: Money::create(95000),
            discrepancy: Money::create(5000),
            zReportNumber: ZReportNumber::create(1),
            zReportHash: ZReportHash::create(hash('sha256', 'test')),
        );

        return $session;
    }

    public function test_returns_last_closed_and_orphan(): void
    {
        $restaurantId = Uuid::generate()->value();
        $command = new GetLastClosedCashSessionCommand(
            restaurantId: $restaurantId,
        );

        $closedSession = $this->createClosedSession();
        $operatorName = 'John Doe';

        $this->cashSessionRepository
            ->shouldReceive('findLastClosedByRestaurant')
            ->once()
            ->andReturn($closedSession);

        $this->cashSessionRepository
            ->shouldReceive('findOrphanByRestaurant')
            ->once()
            ->andReturn(null);

        $userMock = Mockery::mock(User::class);
        $userMock->shouldReceive('name->value')->andReturn($operatorName);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->with($closedSession->openedByUserId()->value())
            ->andReturn($userMock);

        $sale = Sale::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::generate(),
            orderId: Uuid::generate(),
            openedByUserId: Uuid::generate(),
        );

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->with($closedSession->uuid())
            ->andReturn([$sale]);

        $response = ($this->useCase)($command);

        $this->assertNotNull($response->lastClosed);
        $this->assertNull($response->orphanSession);
        $this->assertSame($closedSession->id()->value(), $response->lastClosed->id);
        $this->assertSame($operatorName, $response->lastClosed->operatorName);
        $this->assertSame(1, $response->lastClosed->tickets);
    }

    public function test_returns_null_when_no_data(): void
    {
        $command = new GetLastClosedCashSessionCommand(
            restaurantId: Uuid::generate()->value(),
        );

        $this->cashSessionRepository
            ->shouldReceive('findLastClosedByRestaurant')
            ->once()
            ->andReturn(null);

        $this->cashSessionRepository
            ->shouldReceive('findOrphanByRestaurant')
            ->once()
            ->andReturn(null);

        $response = ($this->useCase)($command);

        $this->assertNull($response->lastClosed);
        $this->assertNull($response->orphanSession);
    }

    public function test_returns_orphan_session(): void
    {
        $restaurantId = Uuid::generate()->value();
        $command = new GetLastClosedCashSessionCommand(
            restaurantId: $restaurantId,
        );

        $orphanSession = CashSession::dddCreate(
            id: Uuid::generate(),
            restaurantId: Uuid::create($restaurantId),
            deviceId: DeviceId::create('orphan-device'),
            openedByUserId: Uuid::generate(),
            initialAmount: Money::create(30000),
        );

        $this->cashSessionRepository
            ->shouldReceive('findLastClosedByRestaurant')
            ->once()
            ->andReturn(null);

        $this->cashSessionRepository
            ->shouldReceive('findOrphanByRestaurant')
            ->once()
            ->andReturn($orphanSession);

        $response = ($this->useCase)($command);

        $this->assertNull($response->lastClosed);
        $this->assertNotNull($response->orphanSession);
        $this->assertSame($orphanSession->id()->value(), $response->orphanSession->id);
        $this->assertSame('orphan-device', $response->orphanSession->deviceId);
    }

    public function test_returns_last_closed_without_operator(): void
    {
        $command = new GetLastClosedCashSessionCommand(
            restaurantId: Uuid::generate()->value(),
        );

        $closedSession = $this->createClosedSession();

        $this->cashSessionRepository
            ->shouldReceive('findLastClosedByRestaurant')
            ->once()
            ->andReturn($closedSession);

        $this->cashSessionRepository
            ->shouldReceive('findOrphanByRestaurant')
            ->once()
            ->andReturn(null);

        $this->userRepository
            ->shouldReceive('findById')
            ->once()
            ->andReturn(null);

        $this->saleRepository
            ->shouldReceive('findByCashSessionId')
            ->once()
            ->andReturn([]);

        $response = ($this->useCase)($command);

        $this->assertNotNull($response->lastClosed);
        $this->assertNull($response->lastClosed->operatorName);
        $this->assertSame(0, $response->lastClosed->tickets);
    }
}
