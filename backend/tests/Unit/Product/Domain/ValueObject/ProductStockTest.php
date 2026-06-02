<?php

namespace Tests\Unit\Product\Domain\ValueObject;

use App\Product\Domain\ValueObject\ProductStock;
use PHPUnit\Framework\TestCase;

class ProductStockTest extends TestCase
{
    public function test_create_with_valid_stock(): void
    {
        $stock = ProductStock::create(10);

        $this->assertInstanceOf(ProductStock::class, $stock);
        $this->assertSame(10, $stock->value());
    }

    public function test_create_with_zero(): void
    {
        $stock = ProductStock::create(0);

        $this->assertSame(0, $stock->value());
    }

    public function test_create_with_negative_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        ProductStock::create(-1);
    }

    public function test_is_sufficient_for_returns_true_when_enough(): void
    {
        $stock = ProductStock::create(10);

        $this->assertTrue($stock->isSufficientFor(5));
    }

    public function test_is_sufficient_for_returns_false_when_not_enough(): void
    {
        $stock = ProductStock::create(10);

        $this->assertFalse($stock->isSufficientFor(15));
    }

    public function test_decrease_returns_new_instance(): void
    {
        $stock = ProductStock::create(10);
        $decreased = $stock->decrease(3);

        $this->assertNotSame($stock, $decreased);
        $this->assertSame(7, $decreased->value());
    }

    public function test_decrease_with_negative_amount_throws_exception(): void
    {
        $stock = ProductStock::create(10);

        $this->expectException(\InvalidArgumentException::class);

        $stock->decrease(-1);
    }

    public function test_increase_returns_new_instance(): void
    {
        $stock = ProductStock::create(10);
        $increased = $stock->increase(5);

        $this->assertNotSame($stock, $increased);
        $this->assertSame(15, $increased->value());
    }

    public function test_increase_with_negative_amount_throws_exception(): void
    {
        $stock = ProductStock::create(10);

        $this->expectException(\InvalidArgumentException::class);

        $stock->increase(-1);
    }
}
