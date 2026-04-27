<?php

namespace Tests\Unit\Order;

use App\Order\Domain\Entity\Order;
use App\Order\Domain\ValueObject\OrderDiners;
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
            OrderDiners::create(4)
        );

        $this->assertInstanceOf(Order::class, $order);
        $this->assertSame($uuid->value(), $order->id()->value());
        $this->assertSame($restaurantId->value(), $order->restaurantId()->value());
        $this->assertSame($tableId->value(), $order->tableId()->value());
        $this->assertTrue($order->status()->isOpen());
    }

    public function test_ddd_create_initializes_with_open_status(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $tableId = Uuid::generate();
        $userId = Uuid::generate();

        $order = Order::dddCreate($uuid, $restaurantId, $tableId, $userId, OrderDiners::create(4));

        $this->assertInstanceOf(OrderStatus::class, $order->status());
        $this->assertSame('open', $order->status()->value());
    }

    public function test_ddd_create_generates_timestamps(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $tableId = Uuid::generate();
        $userId = Uuid::generate();
        $beforeCreation = now();

        $order = Order::dddCreate($uuid, $restaurantId, $tableId, $userId, OrderDiners::create(4));

        $afterCreation = now();

        $this->assertTrue($order->createdAt()->value() >= $beforeCreation);
        $this->assertTrue($order->createdAt()->value() <= $afterCreation);
        $this->assertNull($order->closedAt());
    }
}
