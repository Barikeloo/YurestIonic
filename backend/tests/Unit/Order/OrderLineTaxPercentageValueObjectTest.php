<?php

namespace Tests\Unit\Order;

use App\Order\Domain\ValueObject\OrderLineTaxPercentage;
use PHPUnit\Framework\TestCase;

class OrderLineTaxPercentageValueObjectTest extends TestCase
{
    public function test_create_with_valid_value(): void
    {
        $taxPercentage = OrderLineTaxPercentage::create(21);

        $this->assertInstanceOf(OrderLineTaxPercentage::class, $taxPercentage);
        $this->assertSame(21, $taxPercentage->value());
    }

    public function test_create_with_min_value(): void
    {
        $taxPercentage = OrderLineTaxPercentage::create(0);

        $this->assertSame(0, $taxPercentage->value());
    }

    public function test_create_with_max_value(): void
    {
        $taxPercentage = OrderLineTaxPercentage::create(100);

        $this->assertSame(100, $taxPercentage->value());
    }

    public function test_create_below_min_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OrderLineTaxPercentage::create(-1);
    }

    public function test_create_above_max_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        OrderLineTaxPercentage::create(101);
    }
}
