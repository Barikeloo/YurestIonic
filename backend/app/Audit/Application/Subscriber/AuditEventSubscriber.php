<?php

declare(strict_types=1);

namespace App\Audit\Application\Subscriber;

use App\Audit\Domain\AuditEventDraft;
use App\Audit\Domain\Interfaces\AuditRecorderInterface;
use App\Audit\Domain\ValueObject\ActionSlug;
use App\Shared\Application\Context\RequestContextInterface;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\AuditableEvent;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\ValueObject\Uuid;

/**
 * Turns any AuditableEvent into an audit record, combining the event's domain
 * data with the request-scoped context (who/where).
 */
final readonly class AuditEventSubscriber implements EventSubscriber
{
    public function __construct(
        private AuditRecorderInterface $recorder,
        private RequestContextInterface $context,
    ) {}

    public function subscribedTo(): array
    {
        return [AuditableEvent::class];
    }

    public function handle(DomainEvent $event): void
    {
        if (! $event instanceof AuditableEvent) {
            return;
        }

        $restaurantId = $this->context->restaurantId();
        if ($restaurantId === null && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $event->auditEntityId())) {
            $restaurantId = $event->auditEntityId();
        }
        $userId = $this->context->userId();
        $sessionId = $this->context->sessionId();

        $this->recorder->record(new AuditEventDraft(
            restaurantId: $restaurantId !== null ? Uuid::create($restaurantId) : null,
            slug: ActionSlug::create($event->auditSlug()),
            entityType: $event->auditEntityType(),
            entityId: $event->auditEntityId(),
            userId: $userId !== null ? Uuid::create($userId) : null,
            ipAddress: $this->context->ipAddress(),
            deviceId: $this->context->deviceId(),
            sessionId: $sessionId !== null ? Uuid::create($sessionId) : null,
            before: $event->auditBefore(),
            after: $event->auditAfter(),
            metadata: $event->auditMetadata(),
        ));
    }
}
