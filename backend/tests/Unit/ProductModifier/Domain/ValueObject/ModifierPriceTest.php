<?php

namespace Tests\Unit\ProductModifier\Domain\ValueObject;

use App\ProductModifier\Domain\ValueObject\ModifierPrice;
use PHPUnit\Framework\TestCase;

class ModifierPriceTest extends TestCase
{
    public function test_create_with_valid_price(): void
    {
        $price = ModifierPrice::create(1500);

        $this->assertInstanceOf(ModifierPrice::class, $price);
        $this->assertSame(1500, $price->value());
    }

    public function test_create_with_zero_price(): void
    {
        $price = ModifierPrice::create(0);

        $this->assertSame(0, $price->value());
    }

    public function test_create_with_negative_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ModifierPrice::create(-1);
    }
}
