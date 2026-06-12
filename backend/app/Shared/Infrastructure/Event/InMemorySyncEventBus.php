<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Event;

use App\Shared\Application\Event\EventBusInterface;
use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;

/**
 * Synchronous, in-process event bus: every published event is dispatched to the
 * matching subscribers within the current request, in registration order.
 */
final class InMemorySyncEventBus implements EventBusInterface
{
    /** @var list<EventSubscriber> */
    private array $subscribers;

    public function __construct(EventSubscriber ...$subscribers)
    {
        $this->subscribers = array_values($subscribers);
    }

    public function publish(DomainEvent ...$events): void
    {
        foreach ($events as $event) {
            foreach ($this->subscribers as $subscriber) {
                foreach ($subscriber->subscribedTo() as $type) {
                    if ($event instanceof $type) {
                        $subscriber->handle($event);
                        break;
                    }
                }
            }
        }
    }
}
