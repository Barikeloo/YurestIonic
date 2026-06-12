<?php

declare(strict_types=1);

namespace App\Shared\Domain\Event;

/**
 * Gives an aggregate the ability to record domain events that the application
 * layer pulls and publishes after persisting.
 */
trait RecordsEvents
{
    /** @var list<DomainEvent> */
    private array $recordedEvents = [];

    protected function recordEvent(DomainEvent $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * Returns the recorded events and clears the buffer.
     *
     * @return list<DomainEvent>
     */
    public function pullDomainEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }
}
