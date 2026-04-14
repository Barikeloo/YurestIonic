<?php

namespace Tests\Unit\Order;

use App\Order\Domain\Entity\OrderLine;
use App\Order\Domain\ValueObject\OrderLinePrice;
use App\Order\Domain\ValueObject\OrderLineQuantity;
use App\Order\Domain\ValueObject\OrderLineTaxPercentage;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class OrderLineEntityTest extends TestCase
{
    public function test_ddd_create_builds_entity_with_value_objects(): void
    {
        $id = Uuid::generate();
        $restaurantId = Uuid::generate();
        $orderId = Uuid::generate();
        $productId = Uuid::generate();
        $userId = Uuid::generate();

        $orderLine = OrderLine::dddCreate(
            id: $id,
            restaurantId: $restaurantId,
            orderId: $orderId,
            productId: $productId,
            userId: $userId,
            quantity: OrderLineQuantity::create(2),
            price: OrderLinePrice::create(1500),
            taxPercentage: OrderLineTaxPercentage::create(10),
        );

        $this->assertSame($id->value(), $orderLine->getId()->value());
        $this->assertSame($restaurantId->value(), $orderLine->getRestaurantId()->value());
        $this->assertSame($orderId->value(), $orderLine->getOrderId()->value());
        $this->assertSame($productId->value(), $orderLine->getProductId()->value());
        $this->assertSame($userId->value(), $orderLine->getUserId()->value());
        $this->assertSame(2, $orderLine->getQuantity()->value());
        $this->assertSame(1500, $orderLine->getPrice()->value());
        $this->assertSame(10, $orderLine->getTaxPercentage()->value());
    }
}
