<?php

namespace Tests\Unit\Order;

use App\Order\Domain\ValueObject\OrderLineQuantity;
use PHPUnit\Framework\TestCase;

class OrderLineQuantityValueObjectTest extends TestCase
{
    public function test_create_with_valid_value(): void
    {
        $quantity = OrderLineQuantity::create(2);

        $this->assertInstanceOf(OrderLineQuantity::class, $quantity);
        $this->assertSame(2, $quantity->value());
    }

    public function test_create_with_zero_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OrderLineQuantity::create(0);
    }

    public function test_create_with_negative_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OrderLineQuantity::create(-1);
    }
}
