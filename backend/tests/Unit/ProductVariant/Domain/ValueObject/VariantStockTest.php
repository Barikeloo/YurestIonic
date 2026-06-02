<?php

namespace Tests\Unit\ProductVariant\Domain\ValueObject;

use App\ProductVariant\Domain\ValueObject\VariantStock;
use PHPUnit\Framework\TestCase;

class VariantStockTest extends TestCase
{
    public function test_create_with_valid_stock(): void
    {
        $stock = VariantStock::create(10);

        $this->assertInstanceOf(VariantStock::class, $stock);
        $this->assertSame(10, $stock->value());
    }

    public function test_create_with_zero_stock(): void
    {
        $stock = VariantStock::create(0);

        $this->assertSame(0, $stock->value());
    }

    public function test_create_with_negative_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VariantStock::create(-1);
    }

    public function test_is_sufficient_for(): void
    {
        $stock = VariantStock::create(10);

        $this->assertTrue($stock->isSufficientFor(5));
        $this->assertTrue($stock->isSufficientFor(10));
        $this->assertFalse($stock->isSufficientFor(11));
    }

    public function test_decrease(): void
    {
        $stock = VariantStock::create(10);

        $result = $stock->decrease(3);

        $this->assertSame(7, $result->value());
        $this->assertNotSame($stock, $result);
    }

    public function test_decrease_with_negative_amount_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VariantStock::create(10)->decrease(-1);
    }

    public function test_increase(): void
    {
        $stock = VariantStock::create(10);

        $result = $stock->increase(5);

        $this->assertSame(15, $result->value());
        $this->assertNotSame($stock, $result);
    }

    public function test_increase_with_negative_amount_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        VariantStock::create(10)->increase(-1);
    }

    public function test_immutability(): void
    {
        $stock = VariantStock::create(10);
        $stock->decrease(3);
        $stock->increase(5);

        $this->assertSame(10, $stock->value());
    }
}
