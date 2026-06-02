<?php

namespace Tests\Unit\Audit\Application;

use App\Audit\Application\GetAuditEvent\GetAuditEvent;
use App\Audit\Application\GetAuditEvent\GetAuditEventCommand;
use App\Audit\Application\GetAuditEvent\GetAuditEventResponse;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Exception\AuditLogNotFoundException;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Audit\Domain\ValueObject\Category;
use App\Audit\Domain\ValueObject\Severity;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class GetAuditEventTest extends TestCase
{
    private AuditLogRepositoryInterface&MockInterface $repository;
    private GetAuditEvent $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $this->useCase = new GetAuditEvent($this->repository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_returns_event_when_found(): void
    {
        $restaurantId = Uuid::generate();
        $uuid = Uuid::generate();

        $log = AuditLog::dddCreate(
            uuid: $uuid,
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: 'order-123',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Pedido order-123 creado.',
            integrityHash: 'hash',
            prevHash: null,
        );

        $command = new GetAuditEventCommand(
            restaurantId: $restaurantId->value(),
            uuid: $uuid->value(),
        );

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->with(Mockery::on(fn (Uuid $r): bool => $r->value() === $restaurantId->value()), Mockery::on(fn (Uuid $u): bool => $u->value() === $uuid->value()))
            ->andReturn($log);

        $response = ($this->useCase)($command);

        $this->assertInstanceOf(GetAuditEventResponse::class, $response);
        $this->assertSame($uuid->value(), $response->uuid);
        $this->assertSame('order.created', $response->action);
    }

    public function test_throws_exception_when_not_found(): void
    {
        $restaurantId = Uuid::generate();
        $uuid = Uuid::generate();

        $command = new GetAuditEventCommand(
            restaurantId: $restaurantId->value(),
            uuid: $uuid->value(),
        );

        $this->repository
            ->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);

        $this->expectException(AuditLogNotFoundException::class);
        $this->expectExceptionMessage("Audit log with uuid {$uuid->value()} not found.");

        ($this->useCase)($command);
    }
}
