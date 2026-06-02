<?php

namespace Tests\Unit\Menu\Domain\ValueObject;

use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\ValueObject\MenuSectionChoiceRule;
use PHPUnit\Framework\TestCase;

class MenuSectionChoiceRuleTest extends TestCase
{
    public function test_create_with_valid_values(): void
    {
        $rule = MenuSectionChoiceRule::create(1, 1);

        $this->assertSame(1, $rule->min());
        $this->assertSame(1, $rule->max());
    }

    public function test_create_with_min_zero(): void
    {
        $rule = MenuSectionChoiceRule::create(0, 1);

        $this->assertSame(0, $rule->min());
        $this->assertSame(1, $rule->max());
    }

    public function test_create_with_different_min_max(): void
    {
        $rule = MenuSectionChoiceRule::create(1, 3);

        $this->assertSame(1, $rule->min());
        $this->assertSame(3, $rule->max());
    }

    public function test_create_with_negative_min_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('min must be >= 0');

        MenuSectionChoiceRule::create(-1, 1);
    }

    public function test_create_with_max_less_than_one_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('max must be >= 1');

        MenuSectionChoiceRule::create(0, 0);
    }

    public function test_create_with_min_exceeding_max_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('min cannot exceed max');

        MenuSectionChoiceRule::create(2, 1);
    }

    public function test_choose_one_named_constructor(): void
    {
        $rule = MenuSectionChoiceRule::chooseOne();

        $this->assertSame(1, $rule->min());
        $this->assertSame(1, $rule->max());
        $this->assertTrue($rule->isExactlyOne());
    }

    public function test_is_exactly_one(): void
    {
        $this->assertTrue(MenuSectionChoiceRule::create(1, 1)->isExactlyOne());
        $this->assertFalse(MenuSectionChoiceRule::create(0, 1)->isExactlyOne());
        $this->assertFalse(MenuSectionChoiceRule::create(1, 2)->isExactlyOne());
        $this->assertFalse(MenuSectionChoiceRule::create(2, 3)->isExactlyOne());
    }
}
