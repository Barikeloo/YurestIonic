<?php

namespace Tests\Unit\Menu\Domain\ValueObject;

use App\Menu\Domain\ValueObject\MenuItemExtraPrice;
use PHPUnit\Framework\TestCase;

class MenuItemExtraPriceTest extends TestCase
{
    public function test_create_with_valid_price(): void
    {
        $extraPrice = MenuItemExtraPrice::create(200);

        $this->assertSame(200, $extraPrice->value());
        $this->assertFalse($extraPrice->isZero());
    }

    public function test_create_with_zero(): void
    {
        $extraPrice = MenuItemExtraPrice::create(0);

        $this->assertSame(0, $extraPrice->value());
        $this->assertTrue($extraPrice->isZero());
    }

    public function test_zero_named_constructor(): void
    {
        $extraPrice = MenuItemExtraPrice::zero();

        $this->assertSame(0, $extraPrice->value());
        $this->assertTrue($extraPrice->isZero());
    }

    public function test_create_with_negative_price_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu item extra price must be >= 0.');

        MenuItemExtraPrice::create(-1);
    }
}
