<?php

namespace Tests\Unit\Audit\Application;

use App\Audit\Application\Subscriber\AuditEventSubscriber;
use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Shared\Application\Context\RequestContextInterface;
use App\Shared\Domain\Event\AuditableEvent;
use App\Shared\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

final class FakeAuditableEvent implements AuditableEvent
{
    public function occurredOn(): \DateTimeImmutable { return new \DateTimeImmutable(); }
    public function auditSlug(): string { return 'tax.created'; }
    public function auditEntityType(): string { return 'tax'; }
    public function auditEntityId(): string { return 'entity-uuid-1'; }
    public function auditMetadata(): array { return ['tax_name' => 'IVA', 'percentage' => 21]; }
    public function auditBefore(): ?array { return null; }
    public function auditAfter(): ?array { return ['percentage' => 21]; }
}

class AuditEventSubscriberTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private AuditRecorderInterface&MockInterface $recorder;
    private RequestContextInterface&MockInterface $context;
    private AuditEventSubscriber $subscriber;

    protected function setUp(): void
    {
        $this->recorder = Mockery::mock(AuditRecorderInterface::class);
        $this->context = Mockery::mock(RequestContextInterface::class);
        $this->subscriber = new AuditEventSubscriber($this->recorder, $this->context);
    }

    public function test_subscribes_to_auditable_events(): void
    {
        $this->assertSame([AuditableEvent::class], $this->subscriber->subscribedTo());
    }

    public function test_maps_event_and_context_into_an_audit_draft(): void
    {
        $restaurantUuid = Uuid::generate()->value();
        $userUuid = Uuid::generate()->value();

        $this->context->shouldReceive('restaurantId')->andReturn($restaurantUuid);
        $this->context->shouldReceive('userId')->andReturn($userUuid);
        $this->context->shouldReceive('ipAddress')->andReturn('10.0.0.5');
        $this->context->shouldReceive('deviceId')->andReturn('tablet-1');
        $this->context->shouldReceive('sessionId')->andReturn(null);

        $this->recorder->shouldReceive('record')->once()->with(Mockery::on(
            function (AuditEventDraft $draft) use ($restaurantUuid, $userUuid): bool {
                return $draft->slug->value() === 'tax.created'
                    && $draft->entityType === 'tax'
                    && $draft->entityId === 'entity-uuid-1'
                    && $draft->restaurantId?->value() === $restaurantUuid
                    && $draft->userId?->value() === $userUuid
                    && $draft->ipAddress === '10.0.0.5'
                    && $draft->deviceId === 'tablet-1'
                    && $draft->sessionId === null
                    && $draft->before === null
                    && $draft->after === ['percentage' => 21]
                    && $draft->metadata === ['tax_name' => 'IVA', 'percentage' => 21];
            }
        ));

        $this->subscriber->handle(new FakeAuditableEvent());
    }

    public function test_handles_null_context_gracefully(): void
    {
        $this->context->shouldReceive('restaurantId')->andReturn(null);
        $this->context->shouldReceive('userId')->andReturn(null);
        $this->context->shouldReceive('ipAddress')->andReturn(null);
        $this->context->shouldReceive('deviceId')->andReturn(null);
        $this->context->shouldReceive('sessionId')->andReturn(null);

        $this->recorder->shouldReceive('record')->once()->with(Mockery::on(
            fn (AuditEventDraft $draft): bool => $draft->restaurantId === null && $draft->userId === null
        ));

        $this->subscriber->handle(new FakeAuditableEvent());
    }
}
