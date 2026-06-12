<?php

namespace Tests\Unit\Shared\Event;

use App\Shared\Application\Event\EventSubscriber;
use App\Shared\Domain\Event\DomainEvent;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Infrastructure\Event\InMemorySyncEventBus;
use PHPUnit\Framework\TestCase;

// ── Test doubles ────────────────────────────────────────────────────────────

final class FooEvent implements DomainEvent
{
    public function __construct(private \DateTimeImmutable $at = new \DateTimeImmutable()) {}
    public function occurredOn(): \DateTimeImmutable { return $this->at; }
}

final class BarEvent implements DomainEvent
{
    public function __construct(private \DateTimeImmutable $at = new \DateTimeImmutable()) {}
    public function occurredOn(): \DateTimeImmutable { return $this->at; }
}

/** Records the events it handles, scoped to a given event type. */
final class RecordingSubscriber implements EventSubscriber
{
    /** @var list<DomainEvent> */
    public array $handled = [];

    /** @param class-string $type */
    public function __construct(private string $type) {}

    public function subscribedTo(): array { return [$this->type]; }

    public function handle(DomainEvent $event): void { $this->handled[] = $event; }
}

final class AggregateUsingTrait
{
    use RecordsEvents;

    public function doSomething(DomainEvent $event): void { $this->recordEvent($event); }
}

// ── Tests ───────────────────────────────────────────────────────────────────

class InMemorySyncEventBusTest extends TestCase
{
    public function test_dispatches_event_only_to_matching_subscribers(): void
    {
        $fooSub = new RecordingSubscriber(FooEvent::class);
        $barSub = new RecordingSubscriber(BarEvent::class);
        $bus = new InMemorySyncEventBus($fooSub, $barSub);

        $foo = new FooEvent();
        $bus->publish($foo);

        $this->assertSame([$foo], $fooSub->handled);
        $this->assertSame([], $barSub->handled);
    }

    public function test_dispatches_to_subscribers_by_interface(): void
    {
        // A subscriber listening to the DomainEvent interface gets everything.
        $catchAll = new RecordingSubscriber(DomainEvent::class);
        $bus = new InMemorySyncEventBus($catchAll);

        $foo = new FooEvent();
        $bar = new BarEvent();
        $bus->publish($foo, $bar);

        $this->assertSame([$foo, $bar], $catchAll->handled);
    }

    public function test_handles_subscriber_only_once_per_event(): void
    {
        $sub = new RecordingSubscriber(FooEvent::class);
        $bus = new InMemorySyncEventBus($sub);

        $bus->publish(new FooEvent());

        $this->assertCount(1, $sub->handled);
    }

    public function test_publishing_with_no_events_is_a_noop(): void
    {
        $sub = new RecordingSubscriber(FooEvent::class);
        $bus = new InMemorySyncEventBus($sub);

        $bus->publish();

        $this->assertSame([], $sub->handled);
    }

    public function test_records_events_trait_accumulates_and_clears_on_pull(): void
    {
        $aggregate = new AggregateUsingTrait();
        $e1 = new FooEvent();
        $e2 = new BarEvent();

        $aggregate->doSomething($e1);
        $aggregate->doSomething($e2);

        $this->assertSame([$e1, $e2], $aggregate->pullDomainEvents());
        // Buffer is cleared after pulling.
        $this->assertSame([], $aggregate->pullDomainEvents());
    }
}
