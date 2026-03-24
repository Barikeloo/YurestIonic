<?php

namespace Tests\Unit\Order;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\ValueObject\OrderStatus;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class OrderEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $tableId = Uuid::generate();
        $userId = Uuid::generate();

        $order = Order::dddCreate(
            $uuid,
            $restaurantId,
            $tableId,
            $userId,
            4
        );

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame($uuid->value(), $order->getId()->value());
        $this->assertSame($restaurantId->value(), $order->getRestaurantId()->value());
        $this->assertSame($tableId->value(), $order->getTableId()->value());
        $this->assertTrue($order->getStatus()->isOpen());
    }

    public function test_ddd_create_initializes_with_open_status(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $tableId = Uuid::generate();
        $userId = Uuid::generate();

        $order = Order::dddCreate($uuid, $restaurantId, $tableId, $userId, 4);

        $this->assertInstanceOf(OrderStatus::class, $order->getStatus());
        $this->assertSame('open', $order->getStatus()->value());
    }

    public function test_ddd_create_generates_timestamps(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $tableId = Uuid::generate();
        $userId = Uuid::generate();
        $beforeCreation = now();

        $order = Order::dddCreate($uuid, $restaurantId, $tableId, $userId, 4);

        $afterCreation = now();

        $this->assertTrue($order->getCreatedAt()->value() >= $beforeCreation);
        $this->assertTrue($order->getCreatedAt()->value() <= $afterCreation);
        $this->assertNull($order->getClosedAt());
    }
}
