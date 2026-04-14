<?php

namespace Tests\Unit\Order;

use App\Order\Domain\ValueObject\OrderLinePrice;
use PHPUnit\Framework\TestCase;

class OrderLinePriceValueObjectTest extends TestCase
{
    public function test_create_with_valid_value(): void
    {
        $price = OrderLinePrice::create(1500);

        $this->assertInstanceOf(OrderLinePrice::class, $price);
        $this->assertSame(1500, $price->value());
    }

    public function test_create_with_zero_price(): void
    {
        $price = OrderLinePrice::create(0);

        $this->assertSame(0, $price->value());
    }

    public function test_create_with_negative_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OrderLinePrice::create(-1);
    }
}
