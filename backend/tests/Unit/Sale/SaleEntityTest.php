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

        $sale = Sale::dddCreate(
            $uuid,
            $restaurantId,
            $orderId,
            $userId,
        );

        $this->assertInstanceOf(Sale::class, $sale);
        $this->assertSame($uuid->value(), $sale->getId()->value());
        $this->assertSame($restaurantId->value(), $sale->getRestaurantId()->value());
        $this->assertSame($orderId->value(), $sale->getOrderId()->value());
        $this->assertSame($userId->value(), $sale->getOpenedByUserId()->value());
        $this->assertNull($sale->getClosedByUserId());
        $this->assertSame(0, $sale->getTotal());
    }

    public function test_ddd_create_generates_timestamps(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $orderId = Uuid::generate();
        $userId = Uuid::generate();
        $beforeCreation = now();

        $sale = Sale::dddCreate($uuid, $restaurantId, $orderId, $userId);

        $afterCreation = now();

        $this->assertTrue($sale->getCreatedAt()->value() >= $beforeCreation);
        $this->assertTrue($sale->getCreatedAt()->value() <= $afterCreation);
        $this->assertEquals(
            $sale->getCreatedAt()->value()->getTimestamp(),
            $sale->getUpdatedAt()->value()->getTimestamp()
        );
    }

    public function test_close_sets_ticket_total_and_closer_user(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $orderId = Uuid::generate();
        $openedBy = Uuid::generate();
        $closedBy = Uuid::generate();

        $sale = Sale::dddCreate($uuid, $restaurantId, $orderId, $openedBy);
        $sale->close($closedBy, 1001, 5500);

        $this->assertSame(1001, $sale->getTicketNumber());
        $this->assertSame($closedBy->value(), $sale->getClosedByUserId()?->value());
        $this->assertSame(5500, $sale->getTotal());
    }
}
