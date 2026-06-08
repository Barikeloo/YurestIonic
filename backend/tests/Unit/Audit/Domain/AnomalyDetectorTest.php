<?php

namespace Tests\Unit\Audit\Domain;

use App\Audit\Domain\AnomalyDetector;
use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class AnomalyDetectorTest extends TestCase
{
    private AuditLogRepositoryInterface&MockInterface $repository;
    private AnomalyDetector $detector;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $this->detector = new AnomalyDetector($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_detect_returns_null_for_non_anomaly_event(): void
    {
        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('order.created'),
            entityType: 'order',
            entityId: 'order-1',
        );

        $result = $this->detector->detect($draft);

        $this->assertNull($result);
    }

    public function test_detect_auth_burst_returns_anomaly_when_threshold_exceeded(): void
    {
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();

        $draft = new AuditEventDraft(
            restaurantId: $restaurantId,
            slug: ActionSlug::create('auth.login_pin_failed'),
            entityType: 'user',
            entityId: $userId->value(),
            userId: $userId,
        );

        $this->repository
            ->shouldReceive('countRecentByActionAndUser')
            ->once()
            ->with($restaurantId, Mockery::type(ActionSlug::class), $userId, 300)
            ->andReturn(3);

        $result = $this->detector->detect($draft);

        $this->assertSame('auth_failed_burst', $result);
    }

    public function test_detect_auth_burst_returns_null_when_below_threshold(): void
    {
        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('auth.login_pin_failed'),
            entityType: 'user',
            entityId: 'user-1',
            userId: Uuid::generate(),
        );

        $this->repository
            ->shouldReceive('countRecentByActionAndUser')
            ->once()
            ->andReturn(1);

        $result = $this->detector->detect($draft);

        $this->assertNull($result);
    }

    public function test_detect_auth_burst_without_user_returns_null(): void
    {
        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('auth.login_pin_failed'),
            entityType: 'user',
            entityId: 'user-1',
            userId: null,
        );

        $this->repository->shouldNotReceive('countRecentByActionAndUser');

        $result = $this->detector->detect($draft);

        $this->assertNull($result);
    }

    public function test_detect_caja_mismatch_returns_anomaly_when_delta_non_zero(): void
    {
        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('caja.closed'),
            entityType: 'cash_session',
            entityId: 'session-1',
            metadata: ['delta_final_cents' => 500],
        );

        $result = $this->detector->detect($draft);

        $this->assertSame('caja_mismatch', $result);
    }

    public function test_detect_caja_mismatch_returns_null_when_delta_zero(): void
    {
        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('caja.closed'),
            entityType: 'cash_session',
            entityId: 'session-1',
            metadata: ['delta_final_cents' => 0],
        );

        $result = $this->detector->detect($draft);

        $this->assertNull($result);
    }

    public function test_detect_caja_mismatch_returns_null_when_no_delta(): void
    {
        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('caja.closed'),
            entityType: 'cash_session',
            entityId: 'session-1',
        );

        $result = $this->detector->detect($draft);

        $this->assertNull($result);
    }

    public function test_detect_force_closed_with_mismatch(): void
    {
        $draft = new AuditEventDraft(
            restaurantId: Uuid::generate(),
            slug: ActionSlug::create('caja.force_closed'),
            entityType: 'cash_session',
            entityId: 'session-1',
            metadata: ['delta_final_cents' => -200],
        );

        $result = $this->detector->detect($draft);

        $this->assertSame('caja_mismatch', $result);
    }
}
