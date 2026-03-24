<?php

namespace Tests\Unit\Sale;

use App\Sale\Domain\Entity\Sale;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class SaleEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_attributes(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $orderId = Uuid::generate();
        $userId = Uuid::generate();
        $total = 2000;

        $sale = Sale::dddCreate(
            $uuid,
            $restaurantId,
            $orderId,
            $userId,
            $total
        );

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertSame($uuid->value(), $sale->getId()->value());
        $this->assertSame($restaurantId->value(), $sale->getRestaurantId()->value());
        $this->assertSame($orderId->value(), $sale->getOrderId()->value());
        $this->assertSame($userId->value(), $sale->getUserId()->value());
        $this->assertSame($total, $sale->getTotal());
    }

    public function test_ddd_create_generates_timestamps(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $orderId = Uuid::generate();
        $userId = Uuid::generate();
        $beforeCreation = now();

        $sale = Sale::dddCreate($uuid, $restaurantId, $orderId, $userId, 2000);

        $afterCreation = now();

        $this->assertTrue($sale->getCreatedAt()->value() >= $beforeCreation);
        $this->assertTrue($sale->getCreatedAt()->value() <= $afterCreation);
        $this->assertEquals(
            $sale->getCreatedAt()->value()->getTimestamp(),
            $sale->getUpdatedAt()->value()->getTimestamp()
        );
    }

    public function test_ddd_create_with_monetary_values(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $orderId = Uuid::generate();
        $userId = Uuid::generate();
        $total = 5500;

        $sale = Sale::dddCreate($uuid, $restaurantId, $orderId, $userId, $total);

        $this->assertSame(5500, $sale->getTotal());
    }
}
