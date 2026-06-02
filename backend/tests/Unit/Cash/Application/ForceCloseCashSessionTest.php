<?php

namespace Tests\Unit\Cash\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Application\ForceCloseCashSession\ForceCloseCashSession;
use App\Cash\Application\ForceCloseCashSession\ForceCloseCashSessionCommand;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Exception\CashSessionNotFoundException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ForceCloseCashSessionTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private ForceCloseCashSession $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new ForceCloseCashSession(
            $this->cashSessionRepository,
            $this->auditRecorder,
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

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type(AuditEventDraft::class));

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
        $this->auditRecorder->shouldNotReceive('record');

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

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft): bool {
                return $draft->slug->equals(ActionSlug::create('caja.force_closed'));
            }));

        ($this->useCase)($command);

        $this->assertTrue(true);
    }
}
