<?php

declare(strict_types=1);

namespace Tests\Unit\Tables\Infrastructure\Broadcasting;

use App\Tables\Domain\Event\TablesMerged;
use App\Tables\Domain\Event\TablesUnmerged;
use App\Tables\Infrastructure\Broadcasting\TableStatusChanged;
use App\Tables\Infrastructure\Broadcasting\TablesGroupBroadcastSubscriber;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TablesGroupBroadcastSubscriberTest extends TestCase
{
    private TablesGroupBroadcastSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = new TablesGroupBroadcastSubscriber();
    }

    public function test_subscribed_to_both_table_group_events(): void
    {
        $subscribed = $this->subscriber->subscribedTo();

        $this->assertContains(TablesMerged::class, $subscribed);
        $this->assertContains(TablesUnmerged::class, $subscribed);
        $this->assertCount(2, $subscribed);
    }

    public function test_tables_merged_broadcasts_with_correct_payload(): void
    {
        Event::fake([TableStatusChanged::class]);

        $this->subscriber->handle(new TablesMerged(
            groupId: 'group-uuid',
            tableNames: ['T1', 'T2'],
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(TableStatusChanged::class, function (TableStatusChanged $event): bool {
            return $event->eventType === 'table.merged'
                && $event->groupId === 'group-uuid';
        });
    }

    public function test_tables_unmerged_broadcasts_with_correct_payload(): void
    {
        Event::fake([TableStatusChanged::class]);

        $this->subscriber->handle(new TablesUnmerged(
            groupId: 'group-uuid',
            tableNames: ['T1', 'T2'],
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(TableStatusChanged::class, function (TableStatusChanged $event): bool {
            return $event->eventType === 'table.unmerged'
                && $event->groupId === 'group-uuid';
        });
    }

    public function test_broadcast_goes_to_correct_restaurant_channel(): void
    {
        Event::fake([TableStatusChanged::class]);

        $this->subscriber->handle(new TablesMerged(
            groupId: 'group-uuid',
            tableNames: ['T1', 'T2'],
            restaurantId: 'rest-456',
        ));

        Event::assertDispatched(TableStatusChanged::class, function (TableStatusChanged $event): bool {
            $channels = $event->broadcastOn();
            return $channels->name === 'restaurant.rest-456';
        });
    }

    public function test_broadcast_event_name_is_table_status_changed(): void
    {
        Event::fake([TableStatusChanged::class]);

        $this->subscriber->handle(new TablesUnmerged(
            groupId: 'group-uuid',
            tableNames: ['T3'],
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(TableStatusChanged::class, function (TableStatusChanged $event): bool {
            return $event->broadcastAs() === 'table.status_changed';
        });
    }
}
