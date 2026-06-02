<?php

namespace Tests\Unit\Sale;

use App\Sale\Domain\Entity\Sale;
use App\Sale\Domain\ValueObject\SaleTicketNumber;
use App\Sale\Domain\ValueObject\SaleTotal;
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
        $this->assertSame($uuid->value(), $sale->id()->value());
        $this->assertSame($restaurantId->value(), $sale->restaurantId()->value());
        $this->assertSame($orderId->value(), $sale->orderId()->value());
        $this->assertSame($userId->value(), $sale->openedByUserId()->value());
        $this->assertNull($sale->closedByUserId());
        $this->assertSame(0, $sale->total()->value());
    }

    public function test_ddd_create_generates_timestamps(): void
    {
        $uuid = Uuid::generate();
        $restaurantId = Uuid::generate();
        $orderId = Uuid::generate();
        $userId = Uuid::generate();
        $beforeCreation = new \DateTimeImmutable();

        $sale = Sale::dddCreate($uuid, $restaurantId, $orderId, $userId);

        $afterCreation = new \DateTimeImmutable();

        $this->assertTrue($sale->createdAt()->value() >= $beforeCreation);
        $this->assertTrue($sale->createdAt()->value() <= $afterCreation);
        $this->assertEquals(
            $sale->createdAt()->value()->getTimestamp(),
            $sale->updatedAt()->value()->getTimestamp()
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
        $sale->close($closedBy, SaleTicketNumber::create(1001), SaleTotal::create(5500));

        $this->assertSame(1001, $sale->ticketNumber()?->value());
        $this->assertSame($closedBy->value(), $sale->closedByUserId()?->value());
        $this->assertSame(5500, $sale->total()->value());
    }
}
