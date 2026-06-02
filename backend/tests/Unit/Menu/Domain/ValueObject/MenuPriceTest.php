<?php

namespace Tests\Unit\Menu\Domain\ValueObject;

use App\Menu\Domain\ValueObject\MenuPrice;
use PHPUnit\Framework\TestCase;

class MenuPriceTest extends TestCase
{
    public function test_create_with_valid_price(): void
    {
        $price = MenuPrice::create(1500);

        $this->assertSame(1500, $price->value());
    }

    public function test_create_with_zero(): void
    {
        $price = MenuPrice::create(0);

        $this->assertSame(0, $price->value());
    }

    public function test_create_with_negative_price_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Menu price must be greater than or equal to 0.');

        MenuPrice::create(-1);
    }
}
