<?php

declare(strict_types=1);

namespace Tests\Unit\Order\Infrastructure\Broadcasting;

use App\Order\Domain\Event\OrderCancelled;
use App\Order\Domain\Event\OrderCreated;
use App\Order\Domain\Event\OrderDeleted;
use App\Order\Domain\Event\OrderMarkedToCharge;
use App\Order\Domain\Event\OrderReopened;
use App\Order\Domain\Event\OrderTransferred;
use App\Order\Infrastructure\Broadcasting\OrderStatusChanged;
use App\Order\Infrastructure\Broadcasting\TablesBroadcastSubscriber;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TablesBroadcastSubscriberTest extends TestCase
{
    private TablesBroadcastSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subscriber = new TablesBroadcastSubscriber();
    }

    public function test_subscribed_to_all_six_order_events(): void
    {
        $subscribed = $this->subscriber->subscribedTo();

        $this->assertContains(OrderCreated::class, $subscribed);
        $this->assertContains(OrderCancelled::class, $subscribed);
        $this->assertContains(OrderDeleted::class, $subscribed);
        $this->assertContains(OrderMarkedToCharge::class, $subscribed);
        $this->assertContains(OrderReopened::class, $subscribed);
        $this->assertContains(OrderTransferred::class, $subscribed);
        $this->assertCount(6, $subscribed);
    }

    public function test_order_created_broadcasts_with_correct_payload(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->subscriber->handle(new OrderCreated(
            orderUuid: 'order-uuid',
            tableUuid: 'table-uuid',
            diners: 2,
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event): bool {
            return $event->eventType === 'order.created'
                && $event->orderId === 'order-uuid'
                && $event->tableId === 'table-uuid';
        });
    }

    public function test_order_cancelled_broadcasts_with_correct_payload(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->subscriber->handle(new OrderCancelled(
            orderUuid: 'order-uuid',
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event): bool {
            return $event->eventType === 'order.cancelled'
                && $event->orderId === 'order-uuid';
        });
    }

    public function test_order_marked_to_charge_broadcasts_with_correct_payload(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->subscriber->handle(new OrderMarkedToCharge(
            orderUuid: 'order-uuid',
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event): bool {
            return $event->eventType === 'order.marked_to_charge'
                && $event->orderId === 'order-uuid';
        });
    }

    public function test_order_reopened_broadcasts_with_correct_payload(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->subscriber->handle(new OrderReopened(
            orderUuid: 'order-uuid',
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event): bool {
            return $event->eventType === 'order.reopened'
                && $event->orderId === 'order-uuid';
        });
    }

    public function test_order_transferred_broadcasts_with_from_and_to_table(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->subscriber->handle(new OrderTransferred(
            orderUuid: 'order-uuid',
            fromTableId: 'table-a',
            toTableId: 'table-b',
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event): bool {
            return $event->eventType === 'order.transferred'
                && $event->orderId === 'order-uuid'
                && $event->fromTableId === 'table-a'
                && $event->toTableId === 'table-b';
        });
    }

    public function test_order_deleted_broadcasts_with_table_id_from_before_snapshot(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->subscriber->handle(new OrderDeleted(
            orderUuid: 'order-uuid',
            before: [
                'status' => 'open',
                'diners' => 3,
                'table_id' => 'table-uuid',
            ],
            restaurantId: 'restaurant-uuid',
        ));

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event): bool {
            return $event->eventType === 'order.deleted'
                && $event->orderId === 'order-uuid'
                && $event->tableId === 'table-uuid';
        });
    }

    public function test_broadcast_goes_to_correct_restaurant_channel(): void
    {
        Event::fake([OrderStatusChanged::class]);

        $this->subscriber->handle(new OrderCreated(
            orderUuid: 'order-uuid',
            tableUuid: 'table-uuid',
            diners: 2,
            restaurantId: 'rest-123',
        ));

        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event): bool {
            $channels = $event->broadcastOn();
            return $channels->name === 'restaurant.rest-123';
        });
    }
}
