<?php

namespace Tests\Unit\ProductVariant\Domain\ValueObject;

use App\ProductVariant\Domain\ValueObject\VariantPrice;
use PHPUnit\Framework\TestCase;

class VariantPriceTest extends TestCase
{
    public function test_create_with_valid_price(): void
    {
        $price = VariantPrice::create(1500);

        $this->assertInstanceOf(VariantPrice::class, $price);
        $this->assertSame(1500, $price->value());
    }

    public function test_create_with_zero_price(): void
    {
        $price = VariantPrice::create(0);

        $this->assertSame(0, $price->value());
    }

    public function test_create_with_negative_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VariantPrice::create(-1);
    }
}
