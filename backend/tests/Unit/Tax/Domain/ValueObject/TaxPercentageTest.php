<?php

namespace Tests\Unit\Tax\Domain\ValueObject;

use App\Tax\Domain\ValueObject\TaxPercentage;
use PHPUnit\Framework\TestCase;

class TaxPercentageTest extends TestCase
{
    public function test_create_with_valid_percentage(): void
    {
        $percentage = TaxPercentage::create(21);

        $this->assertInstanceOf(TaxPercentage::class, $percentage);
        $this->assertSame(21, $percentage->value());
    }

    public function test_create_with_zero(): void
    {
        $percentage = TaxPercentage::create(0);

        $this->assertSame(0, $percentage->value());
    }

    public function test_create_with_one_hundred(): void
    {
        $percentage = TaxPercentage::create(100);

        $this->assertSame(100, $percentage->value());
    }

    public function test_create_with_negative_value_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TaxPercentage::create(-1);
    }

    public function test_create_with_value_above_max_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        TaxPercentage::create(101);
    }
}
