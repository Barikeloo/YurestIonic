<?php

declare(strict_types=1);

namespace Tests\Unit\Shared\Domain\ValueObject;

use App\Shared\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_create_from_cents(): void
    {
        $money = Money::create(1500);

        $this->assertInstanceOf(Money::class, $money);
        $this->assertSame(1500, $money->toCents());
        $this->assertSame(15.0, $money->toEuros());
    }

    public function test_create_negative_cents(): void
    {
        $money = Money::create(-500);

        $this->assertSame(-500, $money->toCents());
        $this->assertSame(-5.0, $money->toEuros());
    }

    public function test_create_zero_cents(): void
    {
        $money = Money::create(0);

        $this->assertSame(0, $money->toCents());
        $this->assertSame(0.0, $money->toEuros());
    }

    public function test_from_euros(): void
    {
        $money = Money::fromEuros(19.99);

        $this->assertSame(1999, $money->toCents());
    }

    public function test_from_euros_rounds_correctly(): void
    {
        $money = Money::fromEuros(10.005);

        $this->assertSame(1001, $money->toCents());
    }

    public function test_from_euros_negative(): void
    {
        $money = Money::fromEuros(-5.50);

        $this->assertSame(-550, $money->toCents());
    }

    public function test_zero(): void
    {
        $money = Money::zero();

        $this->assertSame(0, $money->toCents());
        $this->assertTrue($money->isZero());
    }

    public function test_add(): void
    {
        $a = Money::create(1000);
        $b = Money::create(500);

        $result = $a->add($b);

        $this->assertSame(1500, $result->toCents());
        $this->assertNotSame($result, $a);
        $this->assertNotSame($result, $b);
    }

    public function test_add_with_negative(): void
    {
        $a = Money::create(1000);
        $b = Money::create(-300);

        $result = $a->add($b);

        $this->assertSame(700, $result->toCents());
    }

    public function test_subtract(): void
    {
        $a = Money::create(1000);
        $b = Money::create(300);

        $result = $a->subtract($b);

        $this->assertSame(700, $result->toCents());
        $this->assertNotSame($result, $a);
        $this->assertNotSame($result, $b);
    }

    public function test_subtract_larger_amount_returns_negative(): void
    {
        $a = Money::create(300);
        $b = Money::create(1000);

        $result = $a->subtract($b);

        $this->assertSame(-700, $result->toCents());
    }

    public function test_negate(): void
    {
        $money = Money::create(500);

        $result = $money->negate();

        $this->assertSame(-500, $result->toCents());
    }

    public function test_negate_negative(): void
    {
        $money = Money::create(-500);

        $result = $money->negate();

        $this->assertSame(500, $result->toCents());
    }

    public function test_abs_on_positive(): void
    {
        $money = Money::create(500);

        $this->assertSame(500, $money->abs()->toCents());
    }

    public function test_abs_on_negative(): void
    {
        $money = Money::create(-500);

        $this->assertSame(500, $money->abs()->toCents());
    }

    public function test_abs_on_zero(): void
    {
        $money = Money::create(0);

        $this->assertSame(0, $money->abs()->toCents());
    }

    public function test_is_zero(): void
    {
        $this->assertTrue(Money::create(0)->isZero());
        $this->assertFalse(Money::create(1)->isZero());
        $this->assertFalse(Money::create(-1)->isZero());
    }

    public function test_is_positive(): void
    {
        $this->assertTrue(Money::create(1)->isPositive());
        $this->assertFalse(Money::create(0)->isPositive());
        $this->assertFalse(Money::create(-1)->isPositive());
    }

    public function test_is_negative(): void
    {
        $this->assertTrue(Money::create(-1)->isNegative());
        $this->assertFalse(Money::create(0)->isNegative());
        $this->assertFalse(Money::create(1)->isNegative());
    }

    public function test_is_greater_than(): void
    {
        $big = Money::create(1000);
        $small = Money::create(500);

        $this->assertTrue($big->isGreaterThan($small));
        $this->assertFalse($small->isGreaterThan($big));
        $this->assertFalse($big->isGreaterThan($big));
    }

    public function test_is_less_than(): void
    {
        $big = Money::create(1000);
        $small = Money::create(500);

        $this->assertTrue($small->isLessThan($big));
        $this->assertFalse($big->isLessThan($small));
        $this->assertFalse($small->isLessThan($small));
    }

    public function test_equals(): void
    {
        $a = Money::create(1000);
        $b = Money::create(1000);
        $c = Money::create(500);

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }

    public function test_equals_negative(): void
    {
        $a = Money::create(-500);
        $b = Money::create(-500);

        $this->assertTrue($a->equals($b));
    }

    public function test_immutability(): void
    {
        $original = Money::create(1000);
        $original->add(Money::create(500));
        $original->subtract(Money::create(200));
        $original->negate();

        $this->assertSame(1000, $original->toCents());
    }
}
