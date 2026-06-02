<?php

namespace Tests\Unit\Cash\Application;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Cash\Application\OpenCashSession\OpenCashSession;
use App\Cash\Application\OpenCashSession\OpenCashSessionCommand;
use App\Cash\Domain\Entity\CashSession;
use App\Cash\Domain\Exception\ActiveCashSessionAlreadyExistsException;
use App\Cash\Domain\Interfaces\CashSessionRepositoryInterface;
use App\Cash\Domain\ValueObject\DeviceId;
use App\Shared\Domain\ValueObject\Money;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class OpenCashSessionTest extends TestCase
{
    private CashSessionRepositoryInterface&MockInterface $cashSessionRepository;
    private AuditRecorderInterface&MockInterface $auditRecorder;
    private OpenCashSession $useCase;

    protected function setUp(): void
    {
        $this->cashSessionRepository = Mockery::mock(CashSessionRepositoryInterface::class);
        $this->auditRecorder = Mockery::mock(AuditRecorderInterface::class);

        $this->useCase = new OpenCashSession(
            $this->cashSessionRepository,
            $this->auditRecorder,
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

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type(AuditEventDraft::class));

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
        $this->auditRecorder->shouldNotReceive('record');

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

        $this->auditRecorder
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::on(function (AuditEventDraft $draft): bool {
                return $draft->slug->equals(ActionSlug::create('caja.opened'));
            }));

        $response = ($this->useCase)($command);

        $this->assertNull($response->notes);
    }
}
