<?php

namespace Tests\Unit\Audit\Application;

use App\Audit\Application\VerifyAuditChain\VerifyAuditChain;
use App\Audit\Application\VerifyAuditChain\VerifyAuditChainCommand;
use App\Audit\Application\VerifyAuditChain\VerifyAuditChainResponse;
use App\Audit\Domain\AuditChainHasher;
use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Interfaces\AuditLogRepositoryInterface;
use App\Audit\Domain\Interfaces\VerifyChainResultRepositoryInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Audit\Domain\ValueObject\Category;
use App\Audit\Domain\ValueObject\Severity;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

class VerifyAuditChainTest extends TestCase
{
    private AuditLogRepositoryInterface&MockInterface $repository;
    private VerifyChainResultRepositoryInterface&MockInterface $verifyResultRepo;
    private AuditChainHasher $hasher;
    private VerifyAuditChain $useCase;

    protected function setUp(): void
    {
        $this->repository = Mockery::mock(AuditLogRepositoryInterface::class);
        $this->verifyResultRepo = Mockery::mock(VerifyChainResultRepositoryInterface::class);
        $this->hasher = new AuditChainHasher;
        $this->useCase = new VerifyAuditChain($this->repository, $this->hasher, $this->verifyResultRepo);
    }

    protected function tearDown(): void
    {
        Mockery::close();
    }

    public function test_verifies_valid_chain(): void
    {
        $restaurantId = Uuid::generate();

        $event1 = AuditLog::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: '1',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Event 1',
            integrityHash: 'to-be-replaced',
            prevHash: null,
        );

        $event2 = AuditLog::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: '1',
            action: ActionSlug::create('order.closed'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Event 2',
            integrityHash: 'to-be-replaced',
            prevHash: null,
        );

        // Compute actual hashes using the hasher
        $hash1 = $this->hasher->compute(
            prevHash: null,
            uuid: $event1->uuid()->value(),
            restaurantUuid: $event1->restaurantId()->value(),
            createdAtIso: $event1->createdAt()->format('Y-m-d H:i:s'),
            actionSlug: $event1->action()->value(),
            entityType: $event1->entityType(),
            entityId: $event1->entityId(),
            userUuid: $event1->userId()?->value(),
            summary: $event1->summary(),
            metadata: $event1->metadata(),
            before: $event1->before(),
            after: $event1->after(),
        );
        $hash2 = $this->hasher->compute(
            prevHash: $hash1,
            uuid: $event2->uuid()->value(),
            restaurantUuid: $event2->restaurantId()->value(),
            createdAtIso: $event2->createdAt()->format('Y-m-d H:i:s'),
            actionSlug: $event2->action()->value(),
            entityType: $event2->entityType(),
            entityId: $event2->entityId(),
            userUuid: $event2->userId()?->value(),
            summary: $event2->summary(),
            metadata: $event2->metadata(),
            before: $event2->before(),
            after: $event2->after(),
        );

        // Rebuild logs with correct hashes
        $event1Valid = AuditLog::dddCreate(
            uuid: $event1->uuid(),
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: '1',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Event 1',
            integrityHash: $hash1,
            prevHash: null,
            createdAt: $event1->createdAt(),
        );
        $event2Valid = AuditLog::dddCreate(
            uuid: $event2->uuid(),
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: '1',
            action: ActionSlug::create('order.closed'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Event 2',
            integrityHash: $hash2,
            prevHash: $hash1,
            createdAt: $event2->createdAt(),
        );

        $this->repository
            ->shouldReceive('findAllByRestaurantOrdered')
            ->once()
            ->andReturn([$event1Valid, $event2Valid]);

        $this->verifyResultRepo->shouldReceive('save')->once();

        $command = new VerifyAuditChainCommand(restaurantId: $restaurantId->value());
        $response = ($this->useCase)($command);

        $this->assertInstanceOf(VerifyAuditChainResponse::class, $response);
        $this->assertSame(2, $response->totalEvents);
        $this->assertSame(2, $response->verifiedCount);
        $this->assertSame([], $response->brokenEvents);
        $this->assertNull($response->firstBrokenIndex);
        $this->assertTrue($response->isValid);
    }

    public function test_detects_broken_chain(): void
    {
        $restaurantId = Uuid::generate();

        $event1 = AuditLog::dddCreate(
            uuid: Uuid::generate(),
            restaurantId: $restaurantId,
            entityType: 'order',
            entityId: '1',
            action: ActionSlug::create('order.created'),
            category: Category::create('order'),
            severity: Severity::create('info'),
            summary: 'Event 1',
            integrityHash: 'tampered-hash', // does not match computed
            prevHash: null,
        );

        $this->repository
            ->shouldReceive('findAllByRestaurantOrdered')
            ->once()
            ->andReturn([$event1]);

        $this->verifyResultRepo->shouldReceive('save')->once();

        $command = new VerifyAuditChainCommand(restaurantId: $restaurantId->value());
        $response = ($this->useCase)($command);

        $this->assertSame(1, $response->totalEvents);
        $this->assertSame(0, $response->verifiedCount);
        $this->assertCount(1, $response->brokenEvents);
        $this->assertSame(0, $response->firstBrokenIndex);
        $this->assertFalse($response->isValid);
    }

    public function test_returns_valid_for_empty_events(): void
    {
        $restaurantId = Uuid::generate();

        $this->repository
            ->shouldReceive('findAllByRestaurantOrdered')
            ->once()
            ->andReturn([]);

        $this->verifyResultRepo->shouldReceive('save')->once();

        $command = new VerifyAuditChainCommand(restaurantId: $restaurantId->value());
        $response = ($this->useCase)($command);

        $this->assertSame(0, $response->totalEvents);
        $this->assertSame(0, $response->verifiedCount);
        $this->assertSame([], $response->brokenEvents);
        $this->assertNull($response->firstBrokenIndex);
        $this->assertTrue($response->isValid);
    }
}
