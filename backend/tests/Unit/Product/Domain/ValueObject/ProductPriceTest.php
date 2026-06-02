<?php

namespace Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\ValueObject\ProductPrice;
use PHPUnit\Framework\TestCase;

class ProductPriceTest extends TestCase
{
    public function test_create_with_valid_price(): void
    {
        $price = ProductPrice::create(250);

        $this->assertInstanceOf(ProductPrice::class, $price);
        $this->assertSame(250, $price->value());
    }

    public function test_create_with_zero(): void
    {
        $price = ProductPrice::create(0);

        $this->assertSame(0, $price->value());
    }

    public function test_create_with_negative_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductPrice::create(-1);
    }
}
