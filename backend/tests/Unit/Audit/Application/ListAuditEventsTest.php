<?php

namespace Tests\Unit\Audit\Application;

use App\Audit\Application\ListAuditEvents\ListAuditEvents;
use App\Audit\Application\ListAuditEvents\ListAuditEventsCommand;
use App\Audit\Application\ListAuditEvents\ListAuditEventsResponse;
use App\Audit\Domain\AuditLogPage;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\ListAuditLogsCriteria;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Audit\Domain\ValueObject\Category;
use App\Audit\Domain\ValueObject\Severity;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class ListAuditEventsTest extends TestCase
{
    private AuditLogRepositoryInterface&MockInterface $repository;
    private ListAuditEvents $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $this->useCase = new ListAuditEvents($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_events(): void
    {
        $restaurantId = Uuid::generate();
        $log = AuditLog::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: 'order-1',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Pedido creado',
            integrityHash: 'hash123',
            prevHash: null,
        );

        $page = new AuditLogPage([$log], null, null, false);

        $this->repository
            ->shouldReceive('list')
            ->once()
            ->with(Mockery::type(ListAuditLogsCriteria::class))
            ->andReturn($page);

        $command = new ListAuditEventsCommand(restaurantId: $restaurantId->value());
        $response = ($this->useCase)($command);

        $this->assertInstanceOf(ListAuditEventsResponse::class, $response);
        $this->assertCount(1, $response->items);
        $this->assertNull($response->nextCursor);
        $this->assertFalse($response->hasMore);
    }

    public function test_returns_empty_list(): void
    {
        $page = AuditLogPage::empty();

        $this->repository
            ->shouldReceive('list')
            ->once()
            ->andReturn($page);

        $command = new ListAuditEventsCommand(restaurantId: Uuid::generate()->value());
        $response = ($this->useCase)($command);

        $this->assertCount(0, $response->items);
        $this->assertNull($response->nextCursor);
        $this->assertFalse($response->hasMore);
    }

    public function test_returns_events_with_next_cursor(): void
    {
        $restaurantId = Uuid::generate();
        $log = AuditLog::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: 'order-1',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Pedido creado',
            integrityHash: 'hash',
            prevHash: null,
        );

        $now = new \DateTimeImmutable;
        $page = new AuditLogPage([$log], $now, 50, true);

        $this->repository
            ->shouldReceive('list')
            ->once()
            ->andReturn($page);

        $command = new ListAuditEventsCommand(restaurantId: $restaurantId->value());
        $response = ($this->useCase)($command);

        $this->assertCount(1, $response->items);
        $this->assertNotNull($response->nextCursor);
        $this->assertTrue($response->hasMore);
    }

    public function test_filters_are_passed_to_repository(): void
    {
        $restaurantId = Uuid::generate();
        $userId = Uuid::generate();

        $page = AuditLogPage::empty();

        $this->repository
            ->shouldReceive('list')
            ->once()
            ->with(Mockery::on(function (ListAuditLogsCriteria $c) use ($restaurantId): bool {
                return $c->restaurantId->value() === $restaurantId->value()
                    && $c->category === 'caja'
                    && $c->severity === 'warning'
                    && $c->deviceId === 'device-1'
                    && $c->anomalyOnly === true;
            }))
            ->andReturn($page);

        $command = new ListAuditEventsCommand(
            restaurantId: $restaurantId->value(),
            category: 'caja',
            severity: 'warning',
            deviceId: 'device-1',
            anomalyOnly: true,
        );

        $response = ($this->useCase)($command);

        $this->assertCount(0, $response->items);
    }

    public function test_cursor_decoding_invalid_returns_null(): void
    {
        $page = AuditLogPage::empty();

        $this->repository
            ->shouldReceive('list')
            ->once()
            ->andReturn($page);

        $command = new ListAuditEventsCommand(
            restaurantId: Uuid::generate()->value(),
            cursor: 'invalid-base64!!',
        );

        $response = ($this->useCase)($command);

        $this->assertCount(0, $response->items);
    }
}
